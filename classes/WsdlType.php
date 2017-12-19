<?php
/**
 * @package  wsdl.writer
 *
 * @version   $$
 * @author    David Giffin <david@giffin.org>
 * @since     PHP 5.0.2
 * @copyright Copyright (c) 2000-2005 David Giffin : LGPL - See LICENCE
 *
 */

require_once("WsdlProperty.php");

/**
 * WSDL Generator for PHP5
 *
 * @package   wsdl.writer
 * @author    David Giffin <david@giffin.org>
 *
 */
class WsdlType
{
    // see http://www.w3schools.com/Schema/schema_dtypes_string.asp for starting point on data types
    // The List of Primitive WSDL Types
    private static $primitives    = array("string", "int", "array", "boolean", "base64binary", "binary", "float", "date", "time", "datetime" );
    private static $badTypes      = array("mixed", "");
    
    private $className  = null;
    private $orgClassName  = null;
    private $properties = array();
    private $isArray    = false;


    /**
     * The Name of the Class
     *
     *
     * @param string $className The Name of the Type
     * @param string $unmappedClassName If the name of the type was mapped, set this to the original name
     */
    public function __construct($className, $unmappedClassName=null)
    {
        if (self::isArrayTypeClass($className)) {
            $className = self::stripArrayNotation($className);
            $this->isArray = true;
        }
        if(!is_null($unmappedClassName)){
          if($this->isArray){
            $this->orgClassName = self::stripArrayNotation($unmappedClassName);
          }else{
            $this->orgClassName = $unmappedClassName;
          }
        }
        $this->className  = $className;
    }


    /**
     * Does the class name denote an Array?
     *
     * @return bool Returns true if it's an Array Type
     */
    public static function isArrayTypeClass($className)
    {
        if (substr($className, -2) == "[]") {
            return true;
        }
        return false;
    }


    /**
     * Get the WSDL Complex Type Array Name
     * -- Convert ObjName[] or ObjName to ArrayOfObjName
     *
     * @param  string $typeName The Name of the Type
     * @return string The Complex Type Array Name
     */
    public static function getArrayComplexTypeName($typeName)
    {
        if (self::isArrayTypeClass($typeName)) {
            $typeName = self::stripArrayNotation($typeName);
        }
        return "ArrayOf{$typeName}";
    }

 
    /**
     * Strip the Array syntax from the Class Name
     *
     * @param string $className The Array Type Class Name
     * @return string The class name without the array notation
     */
    public static function stripArrayNotation($className)
    {
        return substr($className, 0, (strlen($className) - 2));
    }



    /**
     * Get the Name of the Type
     *
     * @return string The name of the Type
     */
    public function getName()
    {
        if ($this->isArray()) {
            return self::getArrayComplexTypeName($this->className);
        }
        return $this->className;
    }


    /**
     * Get the Underlying Type Name of the Type
     * -- Used for Array Types --
     *
     * @return string The name of the Type
     */
    public function getBaseName()
    {
        return $this->className;
    }


    /**
     * Get the Array Type Name of the Type
     * -- Used for Array Types --
     *
     * @return string The name of the Type
     */
    public function getArrayTypeName()
    {
        return $this->className . '[]';
    }


    /**
     * Get The Elements for this Type
     *
     * @return WsdlProperty[] The list of element objects
     */
    public function getElements()
    {
        // Get all the properties of this type
        $elements = $this->getProperties();

        // Change item[] to ArrayOfitem
        foreach ($elements as &$element) {
            if (self::isArrayTypeClass($element->getType()))
                $element->setType(self::getArrayComplexTypeName($element->getType()));
        }

        return $elements;
    }


    /**
     * Is This an Array Type?
     *
     * @return bool True if it is an Array Type
     */
    public function isArray()
    {
        return $this->isArray;
    }

    /**
     * Is the Type a Complex Type?
     *
     * @return bool True if it is a complex type
     */
    public static function isComplexType($type)
    {
        if (!self::isPrimitiveType($type)) {
            return true;
        }
        return false;
    }


    /**
     * Is the Type a Complex Type?
     *
     * @return bool True if it is a complex type
     */
    public static function isPrimitiveType($type)
    {
        if (in_array(strtolower($type), self::$primitives)) {
            return true;
        }
        return false;
    }


    /**
     * Get all of the Complex Types
     *
     * This method looks through all WsdlMethods
     * to find Object references and creates an
     * array of WsdlTypes for them...
     *
     * @param  array The array of WsdlMethods
     * @param  array Optional assoc array of classmappings
     * @return array The array of WsdlTypes
     */
    public static function getComplexTypes(&$wsdlMethods, $classMappings=array())
    {
        $wsdlTypes = array();
        $argumentTypes = array();
        $invertedClassMappings = array_flip($classMappings);

        // Find the types used as arguments and return parameters to each SOAP method
        foreach ($wsdlMethods as &$method) {

            // Get the WSDL Response and Request
            $request  = $method->getMessageRequest();
            $response = $method->getMessageResponse();

            // Get the Types from the Request
            foreach ($request->parts as &$part) {
                $argumentTypes[] = $part->getBaseType();
            }
            // Get the Types from the Response
            if ($response) {
                foreach ($response->parts as &$part) {
                    $argumentTypes[] = $part->getBaseType();
                }
            }
        }

        // Recurse through each argument and return parameter for each method
        // to build a complete list of complexTypes required by all methods
        $complexTypes = self::findComplexTypes($argumentTypes, array(), $invertedClassMappings);
        
        // Create WSDL type list
        foreach ($complexTypes as $complexType) {
            $orgClassName = null;
            if(array_key_exists($complexType,$invertedClassMappings)){
              $orgClassName = $invertedClassMappings[$complexType];
            }
            $wsdlTypes[] = new WsdlType($complexType, $orgClassName);
        }
        return $wsdlTypes;
    }


    //-----------------------------------
    // Begin Private Methods
    //-----------------------------------


    /**
     * Get the WSDL Properties from the Class File
     *
     * @return WsdlProperty[] An array of properties
     */
    private function getProperties()
    {
        $classname = (is_null($this->orgClassName)? $this->className : $this->orgClassName);
        $reflect        = new ReflectionClass($classname);
        $properties     = $reflect->getProperties();
        $wsdlProperties = array();

        foreach ($properties as &$property) {
            $wsdlProperty     = self::getWsdlProperty($property);
            if ($wsdlProperty) {
                $wsdlProperties[] = $wsdlProperty;
            }
        }
        return $wsdlProperties;
    }


    /**
     * Get information for a Property
     *
     * @param  ReflectionProperty $property The property to get Information for
     * @return WsdlProperty  The Service Information object
     */
    private static function getWsdlProperty(ReflectionProperty $property)
    {
        $propertyInfo  = DocBlockParser::getPropertyInfo($property);

        // Return Nothing if the Property Was not Documented
        if (!isset($propertyInfo['type']) || $propertyInfo['type'] == "") {
            return null;
        }

        $wsdlProperty = new WsdlProperty();
        $wsdlProperty->setName($property->getName());
        $wsdlProperty->setType($propertyInfo['type']);
        $wsdlProperty->setDesc($propertyInfo['desc']);
        if (isset($propertyInfo['optional'])){
          $wsdlProperty->setUsage("optional");
        }
        if (isset($propertyInfo['usage'])){
          $wsdlProperty->setUsage($propertyInfo['usage']);
        }

        return $wsdlProperty;
    }

    /**
     * Find all types needed to fully define the specified types and their properties
     *
     * This function iterates over the supplied type list, recursively scanning all
     * the properties of each type, and all the properties of the types which are
     * members of that type, and so on, while discarding primitive types. A list of
     * the types discovered so far is kept to avoid duplicates and infinite recursion
     * due to circular references.
     *
     * @param array $types List of types to iterate over
     * @param array $foundTypes List of types discovered so far
     * @param array $invertedClassMappings Assoc array of class mappings $type => $org_classname
     * @return array List of types required to fully define all the types in $types
     */
    private static function findComplexTypes(&$types, $foundTypes, $invertedClassMappings=array())
    {
        // Iterate over each type
        foreach ($types as $type) {

            // Only process non-duplicates and non-primitive types
            if (!self::isPrimitiveType($type)
                    && !in_array($type, $foundTypes)
                    && !in_array($type, self::$badTypes)) {

                // First add the top-level type as a found complex type (array or object)
                $foundTypes[] = $type;

                // Find all the properties / public member variables declared in the type
                // Process objects only - not arrays
                if (!self::isArrayTypeClass($type))
                {
                  $classname = (array_key_exists($type,$invertedClassMappings)? $invertedClassMappings[$type] : $type);
                    $reflect        = new ReflectionClass($classname);
                    $properties     = $reflect->getProperties();
                    $searchTypes    = array();

                    foreach ($properties as &$property) {
                        $wsdlProperty     = self::getWsdlProperty($property);
                        if ($wsdlProperty) {
                            $searchTypes[] = $wsdlProperty->getType();
                        }
                    }

                    // Iterate over all the found types to search their properties etc.
                    if (count($searchTypes)) {
                        $foundTypes = self::findComplexTypes($searchTypes, $foundTypes, $invertedClassMappings);
                    }
                }

                // For arrays, we want to ignore primitive type arrays but
                // process the base type of object arrays in case the type isn't
                // referenced anywhere else
                else {
                    $searchTypes = array(self::stripArrayNotation($type));
                    $foundTypes = self::findComplexTypes($searchTypes, $foundTypes, $invertedClassMappings);
                }
            }
        }
        return $foundTypes;
    }
}

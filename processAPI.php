<?php

// VERSION 1.1.1
// BUILD 20180824-002

session_start();

require_once dirname(__FILE__) . '/includes/includes.php';

$peq = new processAPI();

class processAPI
{
	private $_container;
	private $_logger;
    private $_url;

	public function __construct()
	{
		$this->_container = new Container();
		$this->_logger = $this->_container->getLogger();
		$properties = $this->_container->getProperties();
//        $this->_url = "https://storycorps.org/wp-json";
        $this->_url = "https://archive.storycorps.org/wp-json";

		$this->getAPI();
	}

	public function getAPI()
	{
        $tab = '  ';
        $tab2 = $tab . $tab;
        $tab3 = $tab2 . $tab;
		$api = new API($this->_logger);
        $result = $api->getAPI($this->_url);

        $jsonResponse = array();
        $jsonResponse['openapi'] = '3.0.3';
        $jsonResponse['info']['title'] = $result->name;
        $jsonResponse['info']['description'] = $result->description;
        $jsonResponse['info']['version'] = '1.0.0';
        $serverArray['url'] = $this->_url;
        $jsonResponse['servers'] = [$serverArray];

        if (isset($result->routes)) {
            $routesObject = $result->routes;
            $pathsArray = array();
            foreach ($routesObject as $route => $value) {
                if (strpos($route, '(') == true) continue;
                $endPointsArray = $value->endpoints;
                $operationArray = array();
                foreach ($endPointsArray as $endPoint) {
                    $methodArray = $endPoint->methods;
                    $verbObject = array();
                    foreach ($methodArray as $method) {
                        $argsArray = $endPoint->args;
                        $parametersArray = array();
                        foreach ($argsArray as $arg => $value) {
                            $parameterObject = array();
                            $parameterObject["name"] = (!empty($arg)) ? $arg : '';
                            $parameterObject["in"] = ($method == 'GET') ? 'query' : 'header';
                            $parameterObject["required"] = $value->required;
                            if (isset($value->description)) $parameterObject["description"] = $value->description;
                            $schemaObject = array();
                            if (!empty($value->default)) $schemaObject["default"] = $value->default;
                            if (!empty($value->minimum)) $schemaObject["minimum"] = $value->minimum;
                            if (!empty($value->maximum)) $schemaObject["maximum"] = $value->maximum;
                            if (!empty($value->exclusiveMinimum)) $schemaObject["exclusiveMinimum"] = $value->exclusiveMinimum;
                            if (!empty($value->exclusiveMaximum)) $schemaObject["exclusiveMaximum"] = $value->exclusiveMaximum;
                            if (!empty($value->minItems)) $schemaObject["minItems"] = $value->minItems;
                            if (!empty($value->maxItems)) $schemaObject["maxItems"] = $value->maxItems;
                            if (!empty($value->minLength)) $schemaObject["minLength"] = $value->minLength;
                            if (!empty($value->format)) $schemaObject["format"] = $value->format;
                            if (!empty($value->enum)) $schemaObject["enum"] = $value->enum;
                            if (!empty($value->enum) && !is_array($value->type)) $schemaObject["type"] = $value->type;
                            if (!empty($value->oneOf)) $schemaObject["oneOf"] = $value->oneOf;
                            if (!empty($value->pattern)) $schemaObject["pattern"] = $value->pattern;
                            if (!empty($value->items)) {
                                $schemaObject["items"] = $value->items;
                                $itemsArray = $value->items;
                                foreach ($itemsArray as $itemK => $itemV) {
                                    if ($itemK == "properties") {
                                        $propertiesArray = $itemV;
                                        foreach ($propertiesArray as $property) {
                                            if (isset($property->required)) unset($property->required);
                                            if (isset($property->properties)) {
                                                if (count($property->properties) == 0) {
                                                    $property->properties = new stdClass();
                                                }
                                            }
                                            if (isset($property->additionalProperties->type) && is_array($property->additionalProperties->type)) {
                                                unset($property->additionalProperties->type);
                                            }
                                        }
                                    }
                                }
                            }
                            if (!empty($value->properties)) {
                                $schemaObject["properties"] = $value->properties;
                                $propertiesArray = $value->properties;
                                foreach ($propertiesArray as $property) {
                                    if (isset($property->context)) unset($property->context);
                                    if (isset($property->readonly)) unset($property->readonly);
                                    if (isset($property->required)) unset($property->required);
                                }
                            }
                            if (count($schemaObject) > 0) {
                                $parameterObject["schema"] = $schemaObject;
                            } else {
                                $parameterObject["schema"] = new stdClass();
                            }
                            $parametersArray[] = $parameterObject;
                        }
                        $verbObject["parameters"] = $parametersArray;
                        $defaultResponse["description"] = "successful operation";
                        $responseObject["default"] = $defaultResponse;
                        $verbObject["responses"] = $responseObject;
                        $operationArray[strtolower($method)] = $verbObject;
                    }
                }
                $pathsArray[$route] = $operationArray;
            }
            $jsonResponse["paths"] = $pathsArray;
        }

        $this->_response = json_encode($jsonResponse);
        header('Content-type: application/json');
        echo $this->_response;
    }
}
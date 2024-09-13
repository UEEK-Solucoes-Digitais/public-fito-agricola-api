<?php

class KMLClass
{
    public static $coordinates = [];

    public static function getCoordinates($file)
    {
        try {

            // Carregue o arquivo XML (KML)
            $xml = simplexml_load_file("https://fitoagricola-s3.s3.sa-east-1.amazonaws.com/uploads/{$file}");

            // Defina o namespace para facilitar o acesso aos elementos
            $namespaces = $xml->getDocNamespaces();

            if (isset($xml->Document)  && count($xml->Document->children()) > 0) {
                // Itere pelos Placemarks
                foreach ($xml->Document->children() as $item) {
                    if ($item->getName() == 'Placemark') {
                        self::getItemCoordinates($item, $namespaces);
                    } else if ($item->getName() == 'Folder') {
                        foreach ($item->children() as $subItem) {
                            if ($subItem->getName() == 'Placemark') {
                                self::getItemCoordinates($subItem, $namespaces);
                            }
                        }
                    }
                }

                // dd(self::$coordinates);
                return self::$coordinates;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
            report($e);
            return false;
        }
    }

    public static function getItemCoordinates($item, $namespaces)
    {
        $placemark = $item->children($namespaces['']);

        $name = (string)$placemark->name;

        if (isset($placemark->Polygon)) {

            if (isset($placemark->Polygon->outerBoundaryIs) && isset($placemark->Polygon->outerBoundaryIs->LinearRing) && isset($placemark->Polygon->outerBoundaryIs->LinearRing->coordinates)) {
                $xml_coordinates = (string)$placemark->Polygon->outerBoundaryIs->LinearRing->coordinates;

                foreach (explode(" ", $xml_coordinates) as $coordinate) {
                    $explode = explode(",", $coordinate);

                    if (count($explode) >= 2) {
                        self::$coordinates[] = [trim($explode[1]), trim($explode[0])];
                    }
                }
            }
        } else if ($placemark->Point) {
            $xml_coordinates = (string)$placemark->Point->coordinates;
            $explode = explode(",", $xml_coordinates);

            if (count($explode) >= 2) {
                self::$coordinates[] = [trim($explode[1]), trim($explode[0])];
            }
        } else if ($placemark->LineString) {
            $xml_coordinates = (string)$placemark->LineString->coordinates;

            foreach (explode(" ", $xml_coordinates) as $coordinate) {
                $explode = explode(",", $coordinate);

                if (count($explode) >= 2) {
                    self::$coordinates[] = [trim($explode[1]), trim($explode[0])];
                }
            }
        }

        return self::$coordinates;
    }

    public static function getCoordinateText()
    {
        $text = "";
        foreach (self::$coordinates as $coordinate) {
            // $coordinate = explode(",", $coordinate);
            $text .= "{$coordinate[0]},{$coordinate[1]}|||";
        }
        return substr($text, 0, -3);
    }
}

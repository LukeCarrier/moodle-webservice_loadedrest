<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Loaded REST.
 *
 * @package webservice_loadedrest
 * @author Luke Carrier <luke@carrier.im>
 * @copyright 2018 Luke Carrier
 */

namespace webservice_loadedrest\format;

use coding_exception;
use Exception;
use external_description;
use external_multiple_structure;
use external_single_structure;
use external_value;
use invalid_parameter_exception;
use SimpleXMLElement;
use SimpleXMLIterator;
use tool_lp\external;
use XMLWriter;

defined('MOODLE_INTERNAL') || die;

/**
 * XML format.
 */
class xml_format extends abstract_format implements format {
    /**
     * XML version.
     *
     * @var string
     */
    const VERSION = '1.0';

    /**
     * XML document encoding.
     *
     * @var string
     */
    const ENCODING = 'utf-8';

    /**
     * @inheritdoc
     */
    public function get_name() {
        return 'xml';
    }

    /**
     * @inheritdoc
     */
    public function serialise($params) {
        throw new coding_exception('unimplemented');
    }

    /**
     * @inheritdoc
     */
    public function deserialise($body) {
        $oldvalue = libxml_use_internal_errors(true);
        $data = simplexml_load_string($body);
        $errors = libxml_get_errors();
        libxml_use_internal_errors($oldvalue);

        if ($data === false) {
            throw new invalid_parameter_exception(
                    'mangled and hideous though it was, request body could not'
                    . ' be parsed as valid xml');
        }

        return json_decode(json_encode($data), true);
    }

    /**
     * @inheritdoc
     */
    public function send_headers() {
        parent::send_headers();
        header('Content-Type: application/xml; charset=utf-8');
        header('Content-Disposition: inline; filename="response.xml"');
    }

    /**
     * @inheritdoc
     */
    public function send_error(Exception $exception) {
        $doc = new XMLWriter();
        $doc->openMemory();
        $doc->startDocument(static::VERSION, static::ENCODING);
            $doc->startElement('response');
                $doc->startElement('success');
                    $doc->text('false');
                $doc->endElement();
                $doc->startElement('exception');
                    $doc->startAttribute('class');
                        $doc->text(get_class($exception));
                    $doc->endAttribute();
                    $doc->startAttribute('code');
                        $doc->text($exception->getCode());
                    $doc->endAttribute();
                $doc->startElement('message');
                    $doc->text($exception->getMessage());
                $doc->endElement();

        if (debugging() && property_exists($exception, 'debuginfo')) {
            $doc->startElement('debug');
            /** @noinspection PhpUndefinedFieldInspection */
            $doc->text($exception->debuginfo);
            $doc->endElement();
        }

            $doc->endElement();
        $doc->endDocument();
        echo $doc->outputMemory();
    }

    /**
     * @inheritdoc
     */
    public function send_response($result, external_description $description) {
        $doc = new XMLWriter();
        $doc->openMemory();
        $doc->startDocument(static::VERSION, static::ENCODING);
            $doc->startElement('response');
                $this->to_xml($doc, $result, $description);
            $doc->endElement();
        $doc->endDocument();
        echo $doc->outputMemory();
    }

    /**
     * Dump the result to XML.
     *
     * @param XMLWriter $doc
     * @param $result
     * @param external_description $description
     * @param string|null $key
     *
     * @return void
     *
     * @throws coding_exception
     */
    protected function to_xml(XMLWriter $doc, $result, external_description $description, $key=null) {
        $key = $key ?? 'value';

        if ($description instanceof external_value) {
            switch ($description->type) {
                case PARAM_BOOL:
                    $doc->startElement($key);
                    $doc->text($result ? 'true' : 'false');
                    $doc->endElement();
                    break;
                default:
                    $doc->startElement($key);
                    $doc->text($result);
                    $doc->endElement();
            }
        } elseif ($description instanceof external_single_structure) {
            $doc->startElement($key);
            foreach ($description->keys as $key => $keydescription) {
                $this->to_xml($doc, $result[$key], $keydescription, $key);
            }
            $doc->endElement();
        } elseif ($description instanceof external_multiple_structure) {
            $doc->startElement($key);
            foreach ($result as $resultitem) {
                $this->to_xml($doc, $resultitem, $description->content, $key);
            }
            $doc->endElement();
        } else {
            throw new coding_exception(sprintf(
                    'unknown external_description type %s', get_class($description)));
        }
    }

    /**
     * @inheritdoc
     */
    public function never_transform_parameters($parameters, external_description $description) {
        /** @var SimpleXMLElement $parameters */

        if ($description instanceof external_value) {
            return $parameters;
        } elseif ($description instanceof external_single_structure) {
            $values = [];
            foreach ($parameters->children() as $rawvalue) {
                $value = [];
                $rawchildren = $rawvalue->children();
                foreach ($description->keys as $key => $keydescription) {
                    $value[$key] = $rawvalue->{$key};
                    $values[] = $this->transform_parameters($value, $keydescription);
                }
            }

            return $values;
        } elseif ($description instanceof external_multiple_structure) {
            foreach ($parameters->children() as $index => $rawvalue) {
                xdebug_break();
            }
        }

        //if ($description instanceof external_single_structure) {
        //    foreach ($description->keys as $key => $keydescription) {
        //        if ($keydescription instanceof external_single_structure
        //                || $keydescription instanceof external_multiple_structure)
        //            $this->transform_parameters($parameters[$key], $keydescription);
        //    }
        //} elseif ($description instanceof external_multiple_structure) {
        //    $values = [];
        //    foreach ($parameters as $key => &$nestedparameters) {
        //        if (is_array($nestedparameters)) {
        //            foreach ($nestedparameters as &$value) {
        //                $this->transform_parameters($value, $description->content);
        //                $values[] = $value;
        //            }
        //            unset($parameters[$key]);
        //        }
        //    }
        //    if (count($values)) {
        //        foreach ($values as $value) {
        //            $parameters[] = $value;
        //        }
        //    } else {
        //        $parameters = array_values($parameters);
        //    }
        //}
    }
}

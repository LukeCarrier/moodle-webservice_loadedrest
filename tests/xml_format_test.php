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

use webservice_loadedrest\format\xml_format;

defined('MOODLE_INTERNAL') || die;

/**
 * Loaded REST server test suite.
 *
 * @group webservice_loadedrest
 */
class webservice_loadedrest_xml_format_testcase extends advanced_testcase {
    public function data_deserialise() {
        return [
            [
                'body'   => '<request><some>text</some></request>',
                'expect' => [
                    'some' => 'text',
                ],
            ],
            [
                'body'   => '<request><some>1</some></request>',
                'expect' => [
                    'some' => 1,
                ],
            ],
        ];
    }

    /**
     * @dataProvider data_deserialise
     */
    public function test_deserialise($body, $expect) {
        $format = new xml_format();
        $this->assertEquals($expect, $format->deserialise($body));
    }


    /**
     * @expectedException invalid_parameter_exception
     * @expectedExceptionMessage mangled and hideous though it was, request body could not be parsed as valid xml
     */
    public function test_deserialise_throws() {
        $format = new xml_format();
        $format->deserialise('<trololol');
    }

    public function test_send_error() {
        $format = new xml_format();
        $exception = new Exception('message', 1);

        ob_start();
        $format->send_error($exception);
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertRegExp('%\<response\>.*\</response\>%', $output);
        $this->assertRegExp('%\<success\>false\</success\>%', $output);
        $this->assertRegExp('%\<exception%', $output);
        $this->assertRegExp('%class="Exception"%', $output);
        $this->assertRegExp('%code="1"%', $output);
        $this->assertRegExp('%\<message\>message\</message\>%', $output);
    }

    public function data_send_response() {
        return [
            [
                'result' => true,
                'description' => new external_value(PARAM_BOOL),
                'expect' => [
                    '%<value>true</value>%',
                ],
            ],
            [
                'result' => [
                    'key' => 3.14,
                ],
                'description' => new external_single_structure([
                    'key' => new external_value(PARAM_FLOAT),
                ]),
                'expect' => [
                    '%<key>3.14</key>%',
                ],
            ],
            [
                'result' => [
                    [
                        'key' => 3.14,
                    ],
                ],
                'description' => new external_multiple_structure(new external_single_structure([
                    'key' => new external_value(PARAM_FLOAT),
                ])),
                'expect' => [
                    '%<key>3.14</key>%',
                ],
            ],
        ];
    }

    /**
     * @dataProvider data_send_response
     */
    public function test_send_response($result, external_description $description, $expect) {
        $format = new xml_format();

        ob_start();
        $format->send_response($result, $description);
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertRegExp('%\<response\>.*\</response\>%', $output);
        foreach ($expect as $regexp) {
            $this->assertRegExp($regexp, $output);
        }
    }

    public function data_transform_parameters_fixes_multiple_tags() {
        return [
            [
                'body' => '<?xml version="1.0" encoding="utf-8"?>
                        <request>
                            <duplicates>
                                <duplicate>0</duplicate>
                            </duplicates>
                        </request>',
                'description' => new external_function_parameters([
                    'duplicates' => new external_multiple_structure(new external_value(
                            PARAM_INT, '', VALUE_REQUIRED)),
                ]),
                'expected' => [
                    'duplicates' => [0],
                ]
            ],
            [
                'body' => '<?xml version="1.0" encoding="utf-8"?>
                        <request>
                            <duplicates>
                                <duplicate>0</duplicate>
                                <duplicate>1</duplicate>
                            </duplicates>
                        </request>',
                'description' => new external_function_parameters([
                    'duplicates' => new external_multiple_structure(new external_value(
                            PARAM_INT, '', VALUE_REQUIRED)),
                ]),
                'expected' => [
                    'duplicates' => [0, 1],
                ],
            ],
            [
                'body' => '<?xml version="1.0" encoding="utf-8"?>
                        <request>
                            <duplicates>
                                <duplicate>
                                    <moreduplicates>
                                        <moreduplicate>0</moreduplicate>
                                        <moreduplicate>1</moreduplicate>
                                    </moreduplicates>
                                </duplicate>
                                <duplicate>
                                    <moreduplicates>
                                        <moreduplicate>0</moreduplicate>
                                        <moreduplicate>1</moreduplicate>
                                    </moreduplicates>
                                </duplicate>
                            </duplicates>
                        </request>',
                'description' => new external_function_parameters([
                    'duplicates' => new external_multiple_structure(new external_single_structure([
                        'moreduplicates' => new external_multiple_structure(new external_value(
                            PARAM_INT, '', VALUE_REQUIRED)),
                    ])),
                ]),
                'expected' => [
                    'duplicates' => [
                        [
                            'moreduplicates' => [0, 1],
                        ],
                        [
                            'moreduplicates' => [0, 1],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider data_transform_parameters_fixes_multiple_tags
     */
    public function never_test_transform_parameters_fixes_multiple_tags($body, $description, $expected) {
        $format = new xml_format();
        $parameters = $format->parse_request_body($body);
        $format->transform_parameters($parameters, $description);

        $this->assertEquals($expected, $parameters);
    }
}

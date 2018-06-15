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

use webservice_loadedrest\format\json_format;

defined('MOODLE_INTERNAL') || die;

/**
 * Loaded REST server test suite.
 *
 * @group webservice_loadedrest
 */
class webservice_loadedrest_json_format_testcase extends advanced_testcase {
    public function data_deserialise() {
        return [
            [
                'body'   => '{"some":"text"}',
                'expect' => [
                    'some' => 'text',
                ],
            ],
            [
                'body'   => '{"some":1}',
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
        $format = new json_format();
        $this->assertEquals($expect, $format->deserialise($body));
    }

    public function test_deserialise_resets_json_last_error() {
        json_decode('{');
        $this->assertNotEquals(JSON_ERROR_NONE, json_last_error());

        $format = new json_format();
        $this->assertEquals([], $format->deserialise('{}'));
        $this->assertEquals(JSON_ERROR_NONE, json_last_error());
    }

    /**
     * @expectedException invalid_parameter_exception
     * @expectedExceptionMessage request body could not be parsed as valid json
     */
    public function test_deserialise_throws() {
        $format = new json_format();
        $format->deserialise('{');
    }

    public function test_send_error() {
        $format = new json_format();
        $exception = new Exception('message', 1);

        ob_start();
        $format->send_error($exception);
        $output = ob_get_contents();
        ob_end_clean();

        $expected = sprintf(
                '{"success":false,"exception":{"class":"Exception","code":%s,"message":"message"}}',
                1);
        $this->assertEquals($expected, $output);
    }

    public function test_send_error_debuginfo() {
        global $CFG;

        $this->resetAfterTest();
        $format = new json_format();
        $exception = new invalid_parameter_exception('additional info');

        $CFG->debug = DEBUG_NONE;
        ob_start();
        $format->send_error($exception);
        $output = ob_get_contents();
        ob_end_clean();
        $output = json_decode($output);
        $this->assertObjectNotHasAttribute('debug', $output->exception);

        $CFG->debug = DEBUG_DEVELOPER;
        ob_start();
        $format->send_error($exception);
        $output = ob_get_contents();
        ob_end_clean();
        $output = json_decode($output);
        $this->assertEquals('additional info', $output->exception->debug);
    }
}

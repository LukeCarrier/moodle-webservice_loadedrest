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

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamFile;
use webservice_loadedrest\format\format;
use webservice_loadedrest\server;

defined('MOODLE_INTERNAL') || die;

class webservice_loadedrest_server_mock extends server {
    protected function authenticate_by_token($tokentype) {
        global $USER;

        $this->restricted_context = context_system::instance();

        return $USER;
    }

    protected function parse_request() {
        parent::parse_request();
        $this->functionname = 'webservice_loadedrest_external_mock_function';
    }

    protected function load_function_info() {
        $function = (object) [
            'id' => 1000000,
            'name' => $this->functionname,
            'classname' => 'webservice_loadedrest_external_mock',
            'methodname' => 'mock_external_function',
            'classpath' => __FILE__,
            'component' => 'webservice_loadedrest',
            'capabilities' => '',
            'services' => null,
        ];
        $this->function = external_api::external_function_info($function);
    }
}

class webservice_loadedrest_external_mock extends external_api {
    public static function mock_external_function_parameters() {
        return new external_function_parameters([
            'vfsStream' => new external_value(
                    PARAM_TEXT, '', VALUE_REQUIRED),
        ]);
    }

    public static function mock_external_function_returns() {
        return new external_value(
                PARAM_BOOL, '', VALUE_REQUIRED);
    }

    public static function mock_external_function($params) {}
}

/**
 * Loaded REST server test suite.
 *
 * @group webservice_loadedrest
 */
class webservice_loadedrest_server_testcase extends advanced_testcase {
    /**
     * Safely create a mock.
     *
     * PHPUnit 6.0.0 removed the legacy getMock() method in favour of
     * createMock(). Since Moodle 3.1 is stuck on PHPUnit 4.8.x we still need
     * to support the older version.
     *
     * @param string $className
     *
     * @return PHPUnit_Framework_MockObject_MockObject|PHPUnit\Framework\MockObject\MockObject
     */
    public function createMock($originalClassName) {
        $oldVersionClass = 'PHPUnit_Runner_Version';
        $versionClass = class_exists($oldVersionClass)
                ? $oldVersionClass : 'PHPUnit\Runner\Version';

        $version = $versionClass::series();
        if (version_compare($version, '5.4', '>=')) {
            return parent::createMock($originalClassName);
        } else {
            // As compatible as possible with createMock() as introduced in
            // 5.4.0, but without disallowMockingUnknownTypes() as it was
            // introduced in phpunit/phpunit-mock-object v4.0
            // (phpunit/phpunit v6.0):
            // https://github.com/sebastianbergmann/phpunit/blob/5.4.0/src/Framework/TestCase.php#L1510-L1529
            return $this->getMockBuilder($originalClassName)
                ->disableOriginalConstructor()
                ->disableOriginalClone()
                ->disableArgumentCloning()
                ->getMock();
        }
    }

    public function test_invokes_format_correctly() {
        if (!class_exists(vfsStream::class)) {
            $this->markTestSkipped('vfsStream required; available in Moodle >= 3.3');
        }

        $this->resetAfterTest();
        $this->setAdminUser();

        $vfs = vfsStream::setup('webservice_loadedrest', null, [
            'input' => 'vfsStream = life saver',
        ]);
        /** @var vfsStreamFile $inputfile */
        $inputfile = $vfs->getChild('input');

        $mockformat = $this->createMock(format::class);
        $mockformat->expects($this->at(0))
            ->method('get_name')
            ->willReturn('mock');
        $mockformat->expects($this->at(1))
            ->method('deserialise')
            ->with($inputfile->getContent())
            ->willReturn(['vfsStream' => 'life saver']);
        $mockformat->expects($this->at(2))
            ->method('send_headers');
        $mockformat->expects($this->at(3))
            ->method('send_response');

        $server = new webservice_loadedrest_server_mock($mockformat, $inputfile->url());
        $server->run();
    }
}

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

namespace webservice_loadedrest;

use moodle_exception;
use moodle_url;
use webservice_loadedrest\format\format_factory;

defined('MOODLE_INTERNAL') || die;

/**
 * Test webservice client.
 */
class client {
    /**
     * Path to server.php, relative to wwwroot.
     *
     * @var string
     */
    protected $serverurl;

    /**
     * Authentication token.
     *
     * @var string
     */
    protected $token;

    /**
     * I/O format.
     *
     * @var string|null
     */
    protected $formatname;

    /**
     * Initialiser.
     *
     * @param string $baseurl
     * @param string $token
     * @param string|null $format
     */
    public function __construct($baseurl, $token, $format=null) {
        $this->serverurl = $baseurl;
        $this->token = $token;
        $this->formatname = $format ?? format_factory::FORMAT_DEFAULT;
    }

    /**
     * Call an external function.
     *
     * @param $httpmethod
     * @param $wsfunction
     * @param $bodyparams
     *
     * @return mixed
     *
     * @throws moodle_exception
     */
    public function call($httpmethod, $wsfunction, $bodyparams) {
        $format = format_factory::create($this->formatname);

        $getparams = [
            'wstoken'    => $this->token,
            'wsfunction' => $wsfunction,
        ];
        $url = new moodle_url($this->serverurl, $getparams);
        $headers = [
            'Content-Type' => $this->formatname,
        ];
        $body = $format->serialise($bodyparams);

        $curl = curl_init($url->out(false));
        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST  => $httpmethod,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
        ]);
        $result = curl_exec($curl);

        return $format->deserialise($result);
    }
}

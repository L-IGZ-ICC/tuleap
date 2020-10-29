<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\ConcurrentVersionsSystem\ViewVC;

require_once __DIR__ . '/../../../www/include/viewvc_utils.php';
require_once __DIR__ . '/../../../www/cvs/commit_utils.php';

use Codendi_HTMLPurifier;
use ForgeConfig;
use HTTPRequest;
use Project;
use Tuleap\ConcurrentVersionsSystem\ServiceCVS;

class ViewVCProxy
{
    private function displayViewVcHeader($request_uri)
    {
        if (strpos($request_uri, "annotate=") !== false) {
            return true;
        }

        if (
            strpos($request_uri, "view=patch") !== false ||
            $this->isAGraphImageRequest($request_uri) ||
            strpos($request_uri, "view=redirect_path") !== false ||
            // ViewVC will redirect URLs with "&rev=" to "&revision=". This is needed by Hudson.
            strpos($request_uri, "&rev=") !== false
        ) {
            return false;
        }

        if (
            strpos($request_uri, "/?") === false &&
            strpos($request_uri, "&r1=") === false &&
            strpos($request_uri, "&r2=") === false &&
            (strpos($request_uri, "view=") === false ||
                strpos($request_uri, "view=co") !== false )
        ) {
            return false;
        }

        return true;
    }

    private function isAGraphImageRequest($request_uri)
    {
        return strpos($request_uri, "view=graphimg") !== false;
    }

    private function buildQueryString(HTTPRequest $request)
    {
        parse_str($request->getFromServer('QUERY_STRING'), $query_string_parts);
        unset($query_string_parts['roottype']);
        return http_build_query($query_string_parts);
    }

    private function escapeStringFromServer(HTTPRequest $request, $key)
    {
        $string = $request->getFromServer($key);

        return escapeshellarg($string);
    }

    private function setLocaleOnFileName($path)
    {
        $current_locales = setlocale(LC_ALL, "0");
        // to allow $path filenames with French characters
        setlocale(LC_CTYPE, "en_US.UTF-8");

        $encoded_path = escapeshellarg($path);
        setlocale(LC_ALL, $current_locales);

        return $encoded_path;
    }

    private function setLocaleOnCommand($command, &$return_var)
    {
        ob_start();
        putenv("LC_CTYPE=en_US.UTF-8");
        passthru($command, $return_var);

        return ob_get_clean();
    }

    private function getViewVcLocationHeader($location_line)
    {
        // Now look for 'Location:' header line (e.g. generated by 'view=redirect_pathrev'
        // parameter, used when browsing a directory at a certain revision number)
        $location_found = false;

        while ($location_line && ! $location_found && strlen($location_line) > 1) {
            $matches = [];

            if (preg_match('/^Location:(.*)$/', $location_line, $matches)) {
                return $matches[1];
            }

            $location_line = strtok("\n\t\r\0\x0B");
        }

        return false;
    }

    /**
     * @return string
     */
    private function getCVSRootPath(Project $project)
    {
        return ForgeConfig::get('cvs_prefix') . DIRECTORY_SEPARATOR . $project->getUnixNameMixedCase();
    }

    public function displayContent(Project $project, HTTPRequest $request, string $path)
    {
        $user = $request->getCurrentUser();

        viewvc_utils_track_browsing($project->getID(), 'cvs');

        $command = 'REMOTE_USER_ID=' . escapeshellarg($user->getId()) . ' ' .
            'REMOTE_USER=' . escapeshellarg($user->getUserName()) . ' ' .
            'PATH_INFO=' . $this->setLocaleOnFileName($path) . ' ' .
            'QUERY_STRING=' . escapeshellarg($this->buildQueryString($request)) . ' ' .
            'SCRIPT_NAME=/cvs/viewvc.php ' .
            'HTTP_ACCEPT_ENCODING=' . $this->escapeStringFromServer($request, 'HTTP_ACCEPT_ENCODING') . ' ' .
            'HTTP_ACCEPT_LANGUAGE=' . $this->escapeStringFromServer($request, 'HTTP_ACCEPT_LANGUAGE') . ' ' .
            'TULEAP_REPO_NAME=' . escapeshellarg($project->getUnixNameMixedCase()) . ' ' .
            'TULEAP_REPO_PATH=' . escapeshellarg($this->getCVSRootPath($project)) . ' ' .
            __DIR__ . '/viewvc-epel.cgi 2>&1';

        $content = $this->setLocaleOnCommand($command, $return_var);

        if ($return_var === 128) {
            $this->display($project, $user, $this->getPermissionDeniedError($project));
            return;
        }

        [$headers, $body] = http_split_header_body($content);

        $content_type_line   = strtok($content, "\n\t\r\0\x0B");

        $content = substr($content, strpos($content, $content_type_line));

        $location_line   = strtok($content, "\n\t\r\0\x0B");
        $viewvc_location = $this->getViewVcLocationHeader($location_line);

        if ($viewvc_location) {
            $GLOBALS['Response']->redirect($viewvc_location);
        }

        $request_uri = $request->getFromServer('REQUEST_URI');

        $parse = $this->displayViewVcHeader($request_uri);
        if ($parse) {
            $this->display($project, $user, $body);
        } elseif ($this->isAGraphImageRequest($request_uri)) {
            header('Content-Type: image/png');
            echo $body;
            exit();
        } else {
            header('Content-Type: application/octet-stream');
            header('X-Content-Type-Options: nosniff');
            header('Content-Disposition: attachment');
            echo $body;
            exit();
        }
    }

    private function display(Project $project, \PFUser $user, $body)
    {
        $service = $project->getService(\Service::CVS);
        if (! ($service instanceof ServiceCVS)) {
            exit_error(
                $GLOBALS['Language']->getText('global', 'error'),
                $GLOBALS['Language']->getText('cvs_commit_utils', 'error_off')
            );
            return;
        }

        $service->displayCVSRepositoryHeader(
            $user,
            $GLOBALS['Language']->getText('cvs_viewvc', 'title'),
            'browse',
            ['body_class' => ['viewvc-epel']]
        );

        echo util_make_reference_links(
            $body,
            $project->getID()
        );
        site_footer([]);
    }

    private function getPermissionDeniedError(Project $project)
    {
        $purifier = Codendi_HTMLPurifier::instance();
        $url      = session_make_url("/project/memberlist.php?group_id=" . urlencode((string) $project->getID()));

        $title  = $purifier->purify($GLOBALS['Language']->getText('cvs_viewvc', 'error_noaccess'));
        $reason = $GLOBALS['Language']->getText('cvs_viewvc', 'error_noaccess_msg', $purifier->purify($url));

        return '<link rel="stylesheet" href="/viewvc-theme-tuleap/style.css">
            <div class="tuleap-viewvc-header">
                <h3>' . $title . '</h3>
                ' . $reason . '
            </div>';
    }
}
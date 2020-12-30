<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\MailingList;

use EventManager;
use ForgeConfig;
use HTTPRequest;
use IProvideDataAccessResult;
use Project;

class MailingListPresenterCollectionBuilder
{
    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(EventManager $event_manager)
    {
        $this->event_manager = $event_manager;
    }

    /**
     * @return MailingListPresenter[]
     */
    public function build(IProvideDataAccessResult $mailing_lists_result, Project $project, HTTPRequest $request): array
    {
        $scheme      = $request->isSecure() ? 'https://' : 'http://';
        $list_server = $scheme . ForgeConfig::get('sys_lists_host');

        $mailing_list_presenters = [];
        foreach ($mailing_lists_result as $row) {
            $id        = (int) $row['group_list_id'];
            $list_name = $row['list_name'];

            $default_browse_url = $this->getDefaultBrowseUrl($id);

            $mailing_list_presenters[] = new MailingListPresenter(
                $id,
                $list_name,
                $row['description'],
                (bool) $row['is_public'],
                $this->getPublicUrl($list_server, $list_name, $default_browse_url),
                $this->getAdminUrl($list_server, $list_name),
                $this->getUpdateUrl($project, $id),
                $this->getDeleteUrl($project, $id),
                $this->getSubscribeUrl($list_server, $list_name),
                $this->getArchiveUrls($list_server, $list_name, (bool) $row['is_public'], $default_browse_url),
            );
        }

        return $mailing_list_presenters;
    }

    private function getAdminUrl(string $list_server, string $list_name): string
    {
        return $list_server . '/mailman/admin/' . urlencode($list_name) . '/';
    }

    private function getUpdateUrl(Project $project, int $list_id): string
    {
        return '/project/' . urlencode((string) $project->getID())
            . '/admin/mailing-lists/update/' . urlencode((string) $list_id);
    }

    private function getDeleteUrl(Project $project, int $list_id): string
    {
        return '/project/' . urlencode((string) $project->getID())
            . '/admin/mailing-lists/delete/' . urlencode((string) $list_id);
    }

    private function getPublicUrl(string $list_server, string $list_name, string $default_browse_url): string
    {
        if ($default_browse_url) {
            return $default_browse_url;
        }

        return $list_server . '/pipermail/' . urlencode($list_name);
    }

    private function getSubscribeUrl(string $list_server, string $list_name): string
    {
        return $list_server . '/mailman/listinfo/' . \urlencode($list_name);
    }

    /**
     * @return array[]
     * @psalm-return array<array{url: string, label: string}>
     */
    private function getArchiveUrls(
        string $list_server,
        string $list_name,
        bool $is_public,
        string $default_browse_url
    ): array {
        if ($default_browse_url) {
            return [[
                'url'   => $default_browse_url,
                'label' => _('Archives')
            ]];
        }


        if ($is_public) {
            return [[
                'url'   => $this->getPublicUrl($list_server, $list_name, $default_browse_url),
                'label' => _('Archives')
            ]];
        }

        return [
            [
                'url'   => $this->getPublicUrl($list_server, $list_name, $default_browse_url),
                'label' => _('Public archives')
            ],
            [
                'url'   => $list_server . '/mailman/private/' . \urlencode($list_name),
                'label' => _('Private archives')
            ],
        ];
    }

    private function getDefaultBrowseUrl(int $id): string
    {
        $list_url = '';
        $this->event_manager->processEvent(
            'browse_archives',
            [
                'html'    => &$list_url,
                'list_id' => $id,
            ]
        );

        return $list_url;
    }
}
<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

use Tuleap\Project\Admin\Navigation\HeaderNavigationDisplayer;
use Tuleap\Project\Admin\Navigation\NavigationItemPresenter;
use Tuleap\Project\Admin\Navigation\NavigationPresenter;
use Tuleap\ProjectCertification\ProjectAdmin\IndexController;
use Tuleap\ProjectCertification\ProjectAdmin\ProjectOwnerPresenterBuilder;
use Tuleap\ProjectCertification\ProjectOwner\ProjectOwnerDAO;
use Tuleap\ProjectCertification\REST\ProjectCertificationResource;
use Tuleap\Request\CollectRoutesEvent;

require_once __DIR__ . '/../vendor/autoload.php';

class project_certificationPlugin extends Plugin // phpcs:ignore
{
    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);

        bindtextdomain('tuleap-project_certification', __DIR__.'/../site-content');
    }

    public function getPluginInfo()
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new \Tuleap\ProjectCertification\Plugin\PluginInfo($this);
        }

        return $this->pluginInfo;
    }

    public function getHooksAndCallbacks()
    {
        $this->addHook(Event::REGISTER_PROJECT_CREATION);
        $this->addHook(Event::REST_RESOURCES);
        $this->addHook(NavigationPresenter::NAME);
        $this->addHook(CollectRoutesEvent::NAME);

        return parent::getHooksAndCallbacks();
    }

    /**
     * @see \Event::REGISTER_PROJECT_CREATION
     */
    public function registerProjectCreation(array $params)
    {
        $dao = new ProjectOwnerDAO();
        $dao->save($params['group_id'], $params['project_administrator']->getId());
    }

    /**
     * @see \Event::REST_RESOURCES
     */
    public function restResources(array $params)
    {
        $params['restler']->addAPIClass(ProjectCertificationResource::class, 'project_certification');
    }

    public function collectProjectAdminNavigationItems(NavigationPresenter $presenter)
    {
        $project_id = $presenter->getProjectId();
        $html_url = $this->getPluginPath() . '/project/' . urlencode($project_id) . '/admin';
        $presenter->addItem(
            new NavigationItemPresenter(
                dgettext('tuleap-project_certification', 'Project certification'),
                $html_url,
                IndexController::PANE_SHORTNAME,
                $presenter->getCurrentPaneShortname()
            )
        );
    }

    public function collectRoutesEvent(CollectRoutesEvent $routes)
    {
        $routes->getRouteCollector()->addGroup(
            $this->getPluginPath(),
            function (FastRoute\RouteCollector $r) {
                $r->get(
                    '/project/{project_id:\d+}/admin',
                    function () {
                        return new IndexController(
                            TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates'),
                            ProjectManager::instance(),
                            new HeaderNavigationDisplayer(),
                            new ProjectOwnerPresenterBuilder(
                                new ProjectOwnerDAO(),
                                UserManager::instance(),
                                UserHelper::instance()
                            )
                        );
                    }
                );
            }
        );
    }
}

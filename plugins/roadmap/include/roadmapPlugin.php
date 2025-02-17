<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

use Tuleap\CLI\Events\GetWhitelistedKeys;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Roadmap\REST\ResourcesInjector;
use Tuleap\Roadmap\RoadmapProjectWidget;
use Tuleap\Roadmap\RoadmapWidgetDao;

require_once __DIR__ . '/../vendor/autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class RoadmapPlugin extends Plugin
{
    /**
     * Should we display the roadmap plugin that is still under construction?
     *
     * @tlp-config-feature-flag-key
     */
    public const FEATURE_FLAG_KEY = 'plugin_roadmap_display_underconstruction_widget';

    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);
        bindtextdomain('tuleap-roadmap', __DIR__ . '/../site-content');
    }

    public function getPluginInfo(): PluginInfo
    {
        if ($this->pluginInfo === null) {
            $plugin_info = new PluginInfo($this);
            $plugin_info->setPluginDescriptor(
                new PluginDescriptor(
                    dgettext('tuleap-roadmap', 'Roadmap'),
                    '',
                    dgettext('tuleap-roadmap', 'Displays project roadmap as a widget')
                )
            );
            $this->pluginInfo = $plugin_info;
        }

        return $this->pluginInfo;
    }

    public function getDependencies(): array
    {
        return ['tracker'];
    }

    public function getHooksAndCallbacks(): Collection
    {
        $this->addHook(\Tuleap\Widget\Event\GetWidget::NAME);
        $this->addHook(\Tuleap\Widget\Event\GetProjectWidgetList::NAME);
        $this->addHook(GetWhitelistedKeys::NAME);
        $this->addHook(Event::REST_RESOURCES);

        return parent::getHooksAndCallbacks();
    }

    public function getWhitelistedKeys(GetWhitelistedKeys $event): void
    {
        $event->addConfigClass(self::class);
    }

    public function widgetInstance(\Tuleap\Widget\Event\GetWidget $get_widget_event): void
    {
        if (! $this->isFeatureFlagEnabled()) {
            return;
        }

        if ($get_widget_event->getName() === RoadmapProjectWidget::ID) {
            $get_widget_event->setWidget(new RoadmapProjectWidget(
                HTTPRequest::instance()->getProject(),
                new RoadmapWidgetDao(),
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
                \TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates')
            ));
        }
    }

    public function getProjectWidgetList(\Tuleap\Widget\Event\GetProjectWidgetList $event): void
    {
        if (! $this->isFeatureFlagEnabled()) {
            return;
        }

        $event->addWidget(RoadmapProjectWidget::ID);
    }

    private function isFeatureFlagEnabled(): bool
    {
        return (bool) ForgeConfig::getFeatureFlag(self::FEATURE_FLAG_KEY);
    }

    /**
     * @see Event::REST_RESOURCES
     */
    public function restResources(array $params): void
    {
        $injector = new ResourcesInjector();
        $injector->populate($params['restler']);
    }
}

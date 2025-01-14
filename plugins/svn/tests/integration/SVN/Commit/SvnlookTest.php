<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\SVN\Commit;

use ForgeConfig;
use PHPUnit\Framework\TestCase;
use Project;
use Symfony\Component\Process\Process;
use Tuleap\ForgeConfigSandbox;
use Tuleap\SVN\Repository\SvnRepository;
use Tuleap\TemporaryTestDirectory;
use function PHPUnit\Framework\assertEquals;

final class SvnlookTest extends TestCase
{
    use TemporaryTestDirectory;
    use ForgeConfigSandbox;

    /**
     * @var SvnRepository
     */
    private $repository;
    private $working_copy;
    private $svnrepo;

    protected function setUp(): void
    {
        parent::setUp();

        $project         = Project::buildForTest();
        $repository_name = 'somerepo';
        $this->svnrepo   = $this->getTmpDir() . '/svn_plugin/' . $project->getID() . '/' . $repository_name;

        mkdir(dirname($this->svnrepo), 0755, true);

        $this->working_copy = $this->getTmpDir() . '/working_copy';

        (new Process(['svnadmin', 'create', $this->svnrepo]))->mustRun();
        (new Process(['svn', 'mkdir', '-m', 'Base layout', "file://$this->svnrepo/trunk"]))->mustRun();
        (new Process(['svn', 'checkout', "file://$this->svnrepo", $this->working_copy]))->mustRun();

        ForgeConfig::set('sys_data_dir', $this->getTmpDir());

        $this->repository = SvnRepository::buildActiveRepository(2, $repository_name, $project);
    }

    public function testItGetFileSizeDuringTransaction(): void
    {
        $data = 'abc';
        symlink(__DIR__ . '/_fixtures/pre-commit.php', $this->svnrepo . '/hooks/pre-commit');

        file_put_contents($this->working_copy . '/trunk/README', $data);
        (new Process(['svn', 'add', "$this->working_copy/trunk/README"]))->mustRun();
        (new Process(['svn', 'commit', '-m', "add a file", "$this->working_copy/trunk/README"]))->mustRun();

        assertEquals(strlen($data), (int) file_get_contents($this->svnrepo . '/filesize'));
    }
}

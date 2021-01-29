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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

namespace Tuleap\Gitlab\Repository\Webhook\PostMergeRequest;

use DateTimeImmutable;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tuleap\Gitlab\API\ClientWrapper;
use Tuleap\Gitlab\Repository\GitlabRepository;
use Tuleap\Gitlab\Repository\Webhook\Bot\CredentialsRetriever;
use Tuleap\Gitlab\Test\Builder\CredentialsTestBuilder;

class PostMergeRequestWebhookAuthorDataRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CredentialsRetriever
     */
    private $credentials_retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ClientWrapper
     */
    private $gitlab_api_client;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LoggerInterface
     */
    private $logger;
    /**
     * @var PostMergeRequestWebhookAuthorDataRetriever
     */

    protected function setUp(): void
    {
        $this->credentials_retriever = Mockery::mock(CredentialsRetriever::class);
        $this->gitlab_api_client     = Mockery::mock(ClientWrapper::class);
        $this->logger                = Mockery::mock(LoggerInterface::class);

        $this->author_retriever = new PostMergeRequestWebhookAuthorDataRetriever(
            $this->logger,
            $this->gitlab_api_client,
            $this->credentials_retriever,
        );
    }

    public function testItReturnsNullIfNoCredentials(): void
    {
        $repository = new GitlabRepository(
            1,
            2,
            'winter-is-coming',
            'Need more blankets, we are going to freeze our asses',
            'the_full_url',
            new DateTimeImmutable()
        );

        $merge_request_webhook_data = new PostMergeRequestWebhookData(
            'merge_request',
            123,
            'https://example.com',
            2,
            "My Title",
            '',
            'closed',
            (new \DateTimeImmutable())->setTimestamp(1611315112),
            10
        );

        $this->credentials_retriever
            ->shouldReceive('getCredentials')
            ->with($repository)
            ->andReturn(null)
            ->once();

        $author = $this->author_retriever->retrieveAuthorData($repository, $merge_request_webhook_data);

        $this->assertNull($author);
    }

    public function testGitlabApiClientIsCallToGetAuthor(): void
    {
        $repository = new GitlabRepository(
            1,
            2,
            'winter-is-coming',
            'Need more blankets, we are going to freeze our asses',
            'the_full_url',
            new DateTimeImmutable()
        );

        $merge_request_webhook_data = new PostMergeRequestWebhookData(
            'merge_request',
            123,
            'https://example.com',
            2,
            "My Title",
            '',
            'closed',
            (new \DateTimeImmutable())->setTimestamp(1611315112),
            10
        );

        $credentials = CredentialsTestBuilder::get()->build();

        $this->credentials_retriever
            ->shouldReceive('getCredentials')
            ->with($repository)
            ->andReturn($credentials)
            ->once();

        $this->gitlab_api_client
            ->shouldReceive('getUrl')
            ->with($credentials, '/users/10')
            ->andReturn(['name' => 'John', 'email' => 'john@thewall.fr'])
            ->once();

        $author = $this->author_retriever->retrieveAuthorData($repository, $merge_request_webhook_data);

        $this->assertEquals(['name' => 'John', 'email' => 'john@thewall.fr'], $author);
    }
}

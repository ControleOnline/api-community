<?php

namespace App\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
/**
 */
#[ApiResource(operations: [new Get(), new GetCollection(security: 'is_granted(\'IS_AUTHENTICATED_ANONYMOUSLY\')', uriTemplate: '/support/news', controller: \App\Controller\PostsAction::class)], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], security: 'is_granted(\'IS_AUTHENTICATED_ANONYMOUSLY\')')]
final class Posts
{
    /**
     * @var string
     */
    #[ApiProperty(identifier: true)]
    public $id;
    /**
     * @var string
     */
    public $date;
    /**
     * @var string
     */
    public $date_gmt;
    /**
     * @var array
     */
    public $guid;
    /**
     * @var string
     */
    public $modified;
    /**
     * @var string
     */
    public $modified_gmt;
    /**
     * @var string
     */
    public $slug;
    /**
     * @var string
     */
    public $status;
    /**
     * @var string
     */
    public $type;
    /**
     * @var string
     */
    public $link;
    /**
     * @var array
     */
    public $title;
    /**
     * @var array
     */
    public $content;
    /**
     * @var array
     */
    public $excerpt;
    /**
     * @var array
     */
    public $categories;
    /**
     * @var array
     */
    public $tags;
    /**
     * @var string
     */
    public $author;
    /**
     * @var string
     */
    public $featured_file;
}

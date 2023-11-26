<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use App\Library\Utils\WordPress;
use App\Entity\Posts;

class WordPressService
{

  public function __construct(RequestStack $request)
  {
    $this->rq = $request->getCurrentRequest();
  }

  public function search(array $input): array
  {
    $items  = [];
    $result = WordPress::getPosts($input);

    if ($result === null)
      return $items;    

    // create address collection

    foreach ($result as $posts) {      

      $post               	= new Posts();
      $post->id           	= $posts->id;
      $post->date 		  	= $posts->date;
      $post->date_gmt     	= $posts->date_gmt;
      $post->guid         	= $posts->guid;
      $post->modified     	= $posts->modified;
      $post->modified_gmt 	= $posts->modified_gmt;
      $post->slug         	= $posts->slug;
      $post->status       	= $posts->status;
      $post->type         	= $posts->type;
      $post->link         	= $posts->link;
      $post->title        	= $posts->title;
      $post->content      	= $this->formatPost($posts->content);
      $post->excerpt      	= $posts->excerpt;
      $post->categories   	= $posts->categories;
      $post->tags         	= $posts->tags;
      $post->author       	= $posts->author;
      $post->featured_file = $posts->jetpack_featured_media_url;
      $items[] = $post;
    }

    return $items;
  }

  protected function formatPost($content){
      $content->rendered = str_replace('<a ','<a target="_blank" ',$content->rendered);
      return $content;
  }

}

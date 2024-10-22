<?php

class BlueskyImageExtension extends Minz_Extension {

  // set it up!
  public function init() {
    $this->registerHook('entry_before_insert', array($this, 'handleBluesky'));  
  }

  // if its a bsky link, fetch it, rip the image urls out the opengraph tags, then shove em on the end
  // TODO: what's the return type here,
  public function handleBluesky($entry) {
    $link = $entry->link();

    if ($this->isBlueskyURL($link)) {
      // get image urls and post text
      $img_urls = $this->getImageURLs($link);
      $html = $entry->content();

      // TODO: set the title as the original post text? seems like there's no need to do this rn, but
      // that might change if i wrap the post text in any html elements... we'll see
      // $entry->_title($html);

      // now slap the images on it
      // TODO: might be nice to have these be links
      // TODO: maybe wrap everything up inside some divs since bsky rss descriptions are just plain text
      for ($idx = 0; $idx < sizeof($img_urls); $idx++) {
        $img_url = $img_urls[$idx];
        $html .= '<img src="'.$img_url.'" />';
      }

      $entry->_content($html);
    }

    return $entry;
  }

  // check if the entry's link is a bluesky url
  private function isBlueskyURL(string $url): bool {
    $url_info = parse_url($url);
    $hostname = $url_info['host'];
    $hostname = str_replace("www.", "", $hostname);

    return $hostname == "bsky.app";
  }

  // fetch urls of any images in the post from the post's link preview metadata
  private function getImageURLs($link): array { 
    // get the post
    $instance_page = file_get_contents($link);
    $page = new DOMDocument;
    $page->loadHtml($instance_page);

    // iterate over meta tags to get the image urls from all og:image tags
    $metas = $page->getElementsByTagName('meta');
    $img_urls = array();
    for ($idx = 0; $idx < $metas->length; $idx++)
    {
      $meta = $metas->item($idx);
      if ($meta->getAttribute('property') == 'og:image')
        $img_urls[] = $meta->getAttribute('content');
    }

    return $img_urls;
  }
}

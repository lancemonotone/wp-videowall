<?php
/**
 * Template functions for this plugin
 *
 * @package Williams_Meerkat_Videowall
 *
 * @author Williams College WebOps
 * @version 1.0.0
 * @since 1.0.0
 */
if ( ! class_exists('MeerkatVideowallHelper')) {
    class MeerkatVideowallHelper {
        var $debug = false;
        var $application_id = 'Williams College Videowall';
        var $videos = array();
        var $tags = array();
        var $yt, $video_feed, $instance, $widget, $playlist_id, $username, $password, $developer_key, $tag_list, $hide_tags, $hide_intro;

        public function __construct($instance, $widget) {

            //try {
                if ( ! is_array($instance)) {
                    throw new customException(__('You must instantiate an instance of this widget.'));
                }
                $this->instance = $instance;
                $this->widget   = $widget;

                if ( ! $instance['developer_key']) {
                    throw new customException(__('Please provide your Youtube developer key.'));
                }
                $this->developer_key = $instance['developer_key'];

                /*if ( ! $instance['username']) {
                    throw new customException(__('Please provide your Youtube username.'));
                }
                $this->username = $instance['username'];*/

                /*if ( ! $instance['password']) {
                    throw new customException(__('Please provide your Youtube password.'));
                }
                $this->password = $instance['password'];*/

                /*if ( ! $instance['playlist_id']) {
                    echo __('Please provide a Youtube playlist ID.');
                }*/
                $this->playlist_id = $instance['playlist_id'];
                $this->video_size = $instance['video_size'];
                $this->tag_list   = $instance['tag_list'];
                $this->hide_tags  = true; //(bool)$instance['hide_tags'];
                $this->is_ssl     = is_ssl();
            //} catch (customException $e) {
            //    echo customException::errorMessage();
            //}
        }

        /**
         * Stitch together the Videowall.
         *
         */
        function build_videowall() {
            if(!$this->playlist_id) {
                echo 'No playlist_id provided';
                return;
            }

            $time_pre = microtime(true);
            // check to see if the transient exists. set it if it's expired or missing
            //delete_transient('videowall_cached_' . $this->playlist_id);
            if ($this->debug || ! get_transient('videowall_cached_' . $this->playlist_id)) {
                // Get video feed
                $this->get_video_feed();

                // Populate $this->videos and $this->tags with playlist data
                $this->build_videos_and_tags();

                // Print tag filters and videowall <ul> to screen
                if ( ! $this->hide_tags) {
                    $filters = $this->get_filters();
                }
                $videos = $this->get_videos();

                // retrieve html
                $videowall = $filters . $videos;

                // store the result
                if ( ! Wms_Server::instance()->is_dev(true)) {
                    set_transient('videowall_cached_' . $this->playlist_id, $videowall, WEEK_IN_SECONDS);
                } else {
                    set_transient('videowall_cached_' . $this->playlist_id, $videowall);
                }
            }
            // transient is guaranteed to exist now, so return it
            echo get_transient('videowall_cached_' . $this->playlist_id);
            $time_post = microtime(true);
            $elapsed   = $time_post - $time_pre;
            //echo 'Elapsed: ' . $elapsed;
        }

        /**
         * Authenticate with Youtube and retrieve playlist feed
         * @uses Google_Client
         */
        function get_video_feed() {
            $this->yt = $this->getYouTubeService();

            $this->video_feed = $this->yt->playlistItems->listPlaylistItems('id,snippet', array(
                'maxResults' => '50',
                'playlistId' => $this->playlist_id
            ));

            try {
                if ( ! is_a($this->video_feed, 'Google_Service_YouTube_PlaylistItemListResponse')) {
                    throw new customException("Playlist ID: {$this->playlist_id}. This is not a valid feed or there are no videos available.  Please select a different playlist.");
                }
            } catch (customException $e) {
                echo $e->errorMessage();
            }
        }

        /**
         * Build an array of Video Entries
         *
         * @return void
         * @uses function build_videos()
         * @global array $videowallEntries
         *
         * @uses function build_tags()
         */
        function build_videos_and_tags() {
            // Merge video ids
            $videoResults = array();
            foreach ($this->video_feed['items'] as $searchResult) {
                array_push($videoResults, $searchResult->snippet->resourceId->videoId);
            }
            $videoIds = join(',', $videoResults);
            // Call the videos.list method to retrieve location details for each video.
            $videosResponse = $this->yt->videos->listVideos('snippet, recordingDetails, contentDetails', array(
                'id' => $videoIds,
            ));

            foreach ($videosResponse as $video) {
                //$this->printVideoInfo($video);
                if ( ! $this->hide_tags) {
                    $this->build_tags($video);
                }
                $this->build_videos($video);
            }

            try {
                if ( ! count($this->tags) && ! $this->hide_tags) {
                    throw new customException("Playlist ID: {$this->playlist_id}. There are no valid tags in this playlist.");
                }
                if ( ! count($this->videos)) {
                    throw new customException("Playlist ID: {$this->playlist_id}. There are no valid videos in this playlist.");
                }
            } catch (customException $e) {
                echo $e->errorMessage();
            }
        }

        /**
         * Build the filter tag array
         *
         * @param Zend_Gdata_YouTube_VideoEntry $video
         */
        function build_tags($video) {
            $videoId       = $video['snippet']['resourceId']['videoId'];
            $videoResponse = $this->yt->videos->listVideos('snippet', array('id' => $videoId));
            try {
                if (empty($videoResponse)) {
                    throw new customException("Video ID: {$videoId}. There are no videos with this ID. ");
                }
            } catch (customException $e) {
                echo $e->errorMessage();
            }

            $video        = reset($videoResponse['items']);
            $videoSnippet = $video['snippet'];
            $tags         = $videoSnippet['tags'];
            $tag_list     = [];

            if (trim($this->tag_list) != '') {
                $tag_list = explode(',', $this->tag_list);
                // trim each item
                foreach ($tag_list as &$tag_item) {
                    $tag_item = $this->sanitize_string($tag_item);
                }
            }
            foreach ($tags as $tag) {
                $tag_target = $this->sanitize_string($tag);
                if ( ! array_key_exists($this->tags, $tag)) {
                    if (count($tag_list) && ! in_array($tag_target, $tag_list)) {
                        continue;
                    } else {
                        $this->tags[ $tag ] = $tag_target;
                    }
                }
            }
        }

        function sanitize_string($str) {
            $str = preg_replace('/[^a-z\d ]/i', '', $str);

            return strtolower(str_replace(' ', '-', $str));
        }

        /**
         * Build the video items array
         *
         * @param Zend_Gdata_YouTube_VideoEntry $video
         */
        function build_videos($video) {
            switch ($this->video_size) {
                case '240p':
                    $args = array(
                        'width'  => '426',
                        'height' => '240'
                    );
                    break;
                case '360p':
                    $args = array(
                        'width'  => '640',
                        'height' => '360'
                    );
                    break;
                case '480p':
                default:
                    $args = array(
                        'width'  => '840',
                        'height' => '360'
                    );
                    break;
            }
            $gallery_id    = $this->instance['playlist_id'];
            $id            = $video->getId();
            $title         = $video->snippet->getTitle();
            $description   = $video->snippet->getDescription();
            $date_interval = new DateInterval($video->contentDetails->getDuration());
            $duration      = preg_replace('/^0:/', '', $date_interval->format("%h:%i:%S"));
            $thumb_src     = $this->get_video_thumbnail_src($video, 'sddefault');
            $url           = 'https://youtu.be/' . $video->id;
            $yt_args       = array(
                'modestbranding' => '1',
                'rel'            => '0'
            );
            $embed         = wp_oembed_get($url, $args);

            $tags = '';
            /*$tags = $video->getVideoTags();
            foreach($tags as &$tag){
                $tag = $this->sanitize_string($tag);
            }
            $tags = implode(' ', $tags);*/

            $out = <<< EOD
            <li class="item $tags">
                <div class="view">
                <img src="$thumb_src" alt=""/>
                <span class="duration">$duration</span>
                <div class="mask">
                    <h2 class="title">$title</h2>
                    <div class="content">
                        <a  href="#detail-$id"
                            data-featherlight-gallery
                            rel="gallery_$gallery_id">
                            <span class="fab fab-youtube"></span>
                        </a>
                        <div id="detail-$id" class="detail">
                            <div class="feature">
                                <figure class="lazy-yt" id="$id" style="background-image: url($thumb_src)"></figure> 
                                <div class="description">
                                    <h3>$title</h3>
                                    $description
                                </div><!-- end .description -->
                            </div><!-- end .feature -->
                        </div><!-- end .detail -->
                        <div class="description">
                            $description
                        </div><!-- end .description -->
                    </div>
                </div>
            </li>
EOD;
            array_push($this->videos, $out);
        }

        /**
         * Print the filter tag array to screen
         *
         */
        function get_filters() {
            $filter = __('Filter');
            $out    = <<< EOD
            <div class="filters cf">
                <h2 class="filter-heading">$filter:</h2>
                <ul class="filter">
                    <li>
                        <a class="current" href="#" data-filter="*">Show All</a>
                    </li>
EOD;
            foreach ($this->tags as $tag => $target) {
                $out .= <<< EOD
                        <li>
                            <a href="#" data-filter="$target">$tag</a>
                        </li>
EOD;
            }
            $out .= <<< EOD
                </ul>
            </div><!-- .filters -->
EOD;

            return $out;
        }

        /**
         * Get video thumbnail src by size.
         *
         * @param Zend_Gdata_YouTube_PlaylistVideoEntry $videoEntry
         * @param string                                $size
         *
         * @return string url for image src attribute
         *
         * 'default'      120 x 90
         * 'mqdefault'    320 x 180
         * 'hqdefault'    480 x 360
         * 'sddefault'    640 x 480
         * '1'            120 x 90
         * '2'            120 x 90
         * '3'            120 x 90
         */
        function get_video_thumbnail_src($video, $size) {
            $youtube_sizes = array(
                'sddefault',
                'hqdefault',
                'mqdefault',
                'default'
            );
            foreach ($youtube_sizes as $s) {
                $url = "http://i.ytimg.com/vi/" . $video->id . "/" . $s . ".jpg";
                $ch  = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                // don't download content
                curl_setopt($ch, CURLOPT_NOBODY, 1);
                curl_setopt($ch, CURLOPT_FAILONERROR, 1);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                if (curl_exec($ch) !== false) {
                    return $this->fix_ssl($url);
                }
            }

            return false;
        }

        /**
         * Print videos
         */
        function get_videos() {
            $videos = $this->get_video_elements();
            $out    = <<<EOD
            <ul class="items cf">
                $videos
            </ul><!-- #video-container -->
EOD;

            return $out;
        }

        /**
         * Prints each video item to screen.
         */
        function get_video_elements() {
            $videos = array();
            foreach ($this->videos as $video) {
                array_push($videos, $video);
            }

            return join("\n", $videos);
        }

        function fix_ssl($url) {
            if ($this->is_ssl) {
                $url = str_replace('http', 'https', $url);
            }

            return $url;
        }

        /**
         * Return Youtube URL for video.
         *
         * @param Zend_Gdata_YouTube_PlaylistVideoEntry $videoEntry
         *
         * @ignore Testing only
         *
         */
        function printVideoURL($videoEntry) {
            echo apply_filters('the_content', $videoEntry->getVideoWatchPageUrl());
        }

        /**
         * @uses  https://console.cloud.google.com/apis/dashboard?authuser=1&folder=&organizationId=&project=williams-videowall&supportedpurview=project
         */
        function getYouTubeService() {

            //$client_id     = '23144823558.apps.googleusercontent.com'; // Doesn't work anymore. Thanks Google. Can delete.
            $client_id = '215867813609-mna055mo8nhofmfcq1ktr51hui57hqjl.apps.googleusercontent.com';
            //$client_secret = 'RTmw6OFQmGEVp0Od_1fvNMFv'; // Doesn't work anymore. Thanks Google. Can delete.
            $client_secret = 'yt5l3zD3xZe5o_90W7UfV8Zy';
            $scopes        = array('https://www.googleapis.com/auth/youtube');

            $client = new Google_Client();
            $client->setClientId($client_id);
            $client->setClientSecret($client_secret);
            $client->setScopes($scopes);
            $client->setApplicationName($this->application_id);
            $client->setDeveloperKey($this->developer_key);

            return new Google_Service_YouTube($client);
        }

        /**
         * Test Function: Returns VideoEntry data.
         *
         * @param Zend_Gdata_YouTube_PlaylistVideoEntry $videoEntry
         *
         * @ignore Testing only
         *
         */
        function printVideoInfo($videoEntry) {
            // the videoEntry object contains many helper functions
            // that access the underlying mediaGroup object
            echo 'Video: ' . $videoEntry->getVideoTitle() . "<br>";
            echo 'Video ID: ' . $videoEntry->getVideoId() . "<br>";
            echo 'Updated: ' . $videoEntry->getUpdated() . "<br>";
            echo 'Description: ' . $videoEntry->getVideoDescription() . "<br>";
            echo 'Category: ' . $videoEntry->getVideoCategory() . "<br>";
            echo 'Tags: ' . implode(", ", $videoEntry->getVideoTags()) . "<br>";
            echo 'Watch page: ' . $videoEntry->getVideoWatchPageUrl() . "<br>";
            echo 'Flash Player Url: ' . $videoEntry->getFlashPlayerUrl() . "<br>";
            echo 'Duration: ' . $videoEntry->getVideoDuration() . "<br>";
            echo 'View count: ' . $videoEntry->getVideoViewCount() . "<br>";
            echo 'Rating: ' . $videoEntry->getVideoRatingInfo() . "<br>";
            echo 'Geo Location: ' . $videoEntry->getVideoGeoLocation() . "<br>";
            echo 'Recorded on: ' . $videoEntry->getVideoRecorded() . "<br>";

            $this->printVideoURL($videoEntry);

            // see the paragraph above this function for more information on the
            // 'mediaGroup' object. in the following code, we use the mediaGroup
            // object directly to retrieve its 'Mobile RSTP link' child

            foreach ($videoEntry->mediaGroup->content as $content) {
                if ($content->type === "video/3gpp") {
                    echo 'Mobile RTSP link: ' . $content->url . "<br>";
                }
            }

            echo "Thumbnails:<br>";
            $videoThumbnails = $videoEntry->getVideoThumbnails();

            foreach ($videoThumbnails as $videoThumbnail) {
                echo $videoThumbnail['time'] . ' - ' . $videoThumbnail['url'];
                echo ' height=' . $videoThumbnail['height'];
                echo ' width=' . $videoThumbnail['width'] . "<br>";
                ?>
                <img
                        src="<?php echo $videoThumbnail['url'] ?>"/>
                <br><?php
            }
        }
    }
}

if ( ! class_exists('customException')) {
    class customException extends Exception {
        public function errorMessage() {
            //error message
            $errorMsg = '<p><strong>Error on line ' . $this->getLine() . ' in ' . $this->getFile() . ':</strong> <code>' . $this->getMessage() . '</code></p>';

            return $errorMsg;
        }
    }
}

<?php
/*
 * Copyright 2011-2013 Gerwin Sturm
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
  include "config.php";

  $user_ip = $_SERVER["REMOTE_ADDR"];
  if (!$user_ip || $user_ip == "") {
    $user_ip = $_SERVER["SERVER_ADDR"];
  }
  require_once $gapi_client_path . "apiClient.php";
  require_once $gapi_client_path . "contrib/apiPlusService.php";
  session_start();
  $client = new apiClient();
  $client->setApplicationName("All my +");
  $client->setClientId($client_id);
  $client->setClientSecret($client_secret);
  $client->setRedirectUri($base_url . "index.php");
  $client->setDeveloperKey($developer_key);
  $client->setScopes(array("https://www.googleapis.com/auth/plus.me"));
  $plus = new apiPlusService($client);
  $maxresults = 100;

  if (isset($_GET["error"])) {
    header("Location: " . $base_url);
    exit;
  }

  if ($_POST["userid"] && $_POST["userid"] != "") {
    $pattern = "/[0-9]{10,30}/";
    if (preg_match($pattern, $_POST["userid"], $matches)) {
      header("Location: " . $base_url . "u/" . $matches[0]);
      exit;
    } else {
      $pattern = "/(\+\w+)/";
      if (preg_match($pattern, $_POST["userid"], $matches)) {
        header("Location: " . $base_url . "u/" . $matches[0]);
        exit;
      } else {
        header("Location: " . $base_url);
        exit;
      }
    }
  } else {
    if ($_POST["keyword"] && $_POST["keyword"] != "") {
      header("Location: " . $base_url . "s/" . urlencode(trim($_POST["keyword"])));
      exit;
    }
  }

  $q_user = "";
  $q_keyword = "";
  $request = $_SERVER["REQUEST_URI"];
  $p = strrpos($request, "/u/");
  if (!($p === false)) {
    $request = substr($request, $p);
    if ($request == "?quota_exceeded") {
      $q_user = "";
    } else {
      $p = strrpos($request, "?");
      if (!($p === false)) {
        $q_user = substr($request, 3, $p-3);
      } else {
        $q_user = substr($request, 3);
      }
    }
  } else {
    $p = strrpos($request, "/s/");
    if (!($p === false)) {
      $request = substr($request, $p);
      $p = strrpos($request, "?");
      if (!($p === false)) {
        $q_keyword = substr($request, 3, $p-3);
      } else {
        $q_keyword = substr($request, 3);
      }
      $q_keyword = urldecode($q_keyword);
    }
  }

  $show_login = true;
  $str_author_id = "";
  $str_author_name = "";
  $str_author_url = "";
  $str_author_pic = "";
  $str_community_id = "";
  $str_community_name = "";
  $str_errors = "";

  if (trim($q_keyword) != "") {
    $show_login = false;
  }

  if ($q_user != "") {
    $optParams = array("userIp" => $user_ip);
    try {
      $actor = $plus->people->get($q_user, $optParams);
      if (isset($actor["id"])) {
        $str_author_id = $actor["id"];
        $str_author_name = $actor["displayName"];
        $str_author_url = $actor["url"];
        if (isset($actor["image"])){
          if (isset($actor["image"]["url"])){
            $str_author_pic = $actor["image"]["url"];
            $str_author_pic = str_replace("?sz=50", "?sz=200", $str_author_pic);
          }
        }
        $show_login = false;
      }
    } catch (Exception $e) {
      $str_errors = $str_errors . $e->getMessage() . "<br>";
    }

    if ($str_author_name == "") {
      $optParams = array("userIp" => $user_ip, "maxResults" => 1);
      try {
        $community = $plus->activities->listActivities($q_user, "public", $optParams);
        if (isset($community["items"])) {
          if (count($community["items"]) > 0) {
            $cpost = $community["items"][0];
            if (isset($cpost["access"])) {
              if (isset($cpost["access"]["description"])) {
                $str_community_id = $q_user;
                $str_community_name = $cpost["access"]["description"];
                $i = strrpos($str_community_name, "(");
                if (!($i1 === false)) {
                  $str_community_name = trim(substr($str_community_name, 0, $i));
                }

                $comm = array();
                $comm["id"] = $str_community_id;
                $comm["displayName"] = $str_community_name;
                $show_login = false;
              }
            }
          }
        }
      } catch (Exception $e) {
        $str_errors = $str_errors . $e->getMessage() . "<br>";
      }
    }
  }
?>
<!DOCTYPE html>
<html itemscope itemtype="http://schema.org/Person">
<head>
  <meta charset="UTF-8">
<?php
  if ($str_author_name == "") {
    if ($q_keyword == "") {
      if ($str_community_name == "") {
        printf("  <title>All my + Statistics</title>\n");
        printf("  <meta itemprop=\"name\" content=\"All my +\">\n");
        printf("  <meta itemprop=\"description\" content=\"A quick overview and statistics of your public Google+ activities.\">\n");
      } else {
        printf("  <title>All my + Statistics for %s</title>\n", $str_community_name);
        printf("  <meta itemprop=\"name\" content=\"All my + Statistics for %s\">\n", $str_community_name);
        printf("  <meta itemprop=\"description\" content=\"A quick overview and statistics of the Google+ activities for %s.\">\n", $str_community_name);
        printf("  <meta itemprop=\"image\" content=\"%simages/community.png\">\n",$base_url);
      }
    } else {
      printf("  <title>All my + Statistics for %s</title>\n", $q_keyword);
      printf("  <meta itemprop=\"name\" content=\"All my + Statistics for %s\">\n",$q_keyword);
      printf("  <meta itemprop=\"description\" content=\"A quick overview and statistics of the Google+ activities about %s.\">\n",$q_keyword);
      printf("  <meta itemprop=\"image\" content=\"%simages/search.png\">\n",$base_url);
    }
  } else {
    printf("  <title>All my + Statistics for %s</title>\n",$str_author_name);
    printf("  <meta itemprop=\"name\" content=\"All my + Statistics for %s\">\n",$str_author_name);
    printf("  <meta itemprop=\"description\" content=\"A quick overview and statistics of the Google+ activities of %s.\">\n",$str_author_name);
    printf("  <meta itemprop=\"image\" content=\"%s\">\n",$str_author_pic);
  }
?>
  <link rel="stylesheet" type="text/css" href="<?php echo $base_url; ?>css/allmyplus.css">
  <link rel="stylesheet" type="text/css" href="http://code.jquery.com/ui/1.10.2/themes/smoothness/jquery-ui.css">
  <link rel="shortcut icon" href="/favicon.ico">
  <link rel="icon" href="/favicon.ico">
  <base target="_blank">
  <script type="text/javascript">
    var base_url = "<?php echo $base_url; ?>";
  </script>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
  <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1/jquery-ui.min.js"></script>
  <script src="https://www.google.com/jsapi"></script>
  <script src="<?php echo $base_url; ?>scripts/jquery.jsonp-2.3.0.min.js"></script>
  <script src="<?php echo $base_url; ?>scripts/sorttable.js"></script>
  <script src="<?php echo $base_url; ?>scripts/allmyplus.js"></script>
  <script src="https://apis.google.com/js/client:plusone.js">{parsetags: "explicit"}</script>
  <script type="text/javascript">
    $(function () {
      var allmyplus = new AllMyPlus(window, "<?php echo $base_url; ?>", "<?php echo $developer_key; ?>", <?php echo json_encode($actor); ?>, <?php echo json_encode($q_keyword); ?>, <?php echo json_encode($comm); ?>, "<?php echo $client_id; ?>");
    });
  </script>
</head>
<body>
  <div id="header">
    <div id="header1"></div>
    <div id="header2">
      <div id="header2_info">
        <table style="width: 100%;"><tr>
          <td style="width: 70px;"><a href="<?php echo $base_url; ?>" target="_self"><img src="<?php echo $base_url; ?>images/allmy+.png" alt="All my +"></a></td>
          <td>
            <table><tr style="vertical-align: center;">
              <td><h1 id="title">All my + Statistics</h1></td>
              <td id="progress"><img src="<?php echo $base_url; ?>images/spinner.gif" alt="spinner"> Preparing data, please wait...</td>
            </tr></table>
          </td>
        </tr></table>
      </div>
    </div>
  </div>

  <div class="menubar">
    <a class="menue" id="men_start" href="#start" target="_self" style="display: none;">Start</a>
    <a class="menue" id="men_overview" href="#overview" target="_self" style="display: none;">Overview</a>
    <a class="menue" id="men_locations" href="#locations" target="_self" style="display: none;">Locations</a>
    <a class="menue" id="men_charts" href="#charts" target="_self" style="display: none;">Charts</a>
    <a class="menue" id="men_popular" href="#popular" target="_self" style="display: none;">Popular posts</a>
    <a class="menue" id="men_people" href="#people" target="_self" style="display: none;">People</a>
    <a class="menue" id="men_photos" href="#photos" target="_self" style="display: none;">Photos</a>
    <a class="menue" id="men_posts" href="#posts" target="_self" style="display: none;">Posts</a>
    <a class="menue" id="men_data" href="#data" target="_self" style="display: none;">Raw Data</a>
  </div>

  <div id="main">
    <div id="start" class="anchor"></div>
     <div id="d_start" class="contents">
      <table style="width: 100%;"><tr>
        <td style="text-align: center; width: 400px;">
          <div id="user_data" style="display: none;">
            <table><tr>
              <td><a href="#" id="user_pic"><img src="<?php echo $base_url; ?>images/noimage.png" alt="No User" title="No User"></a></td>
              <td><a href="#" id="user_name">No User</a><br><br><span id="user_activities"></span><br><br><span id="share"></span></td>
            </tr></table>
          </div>
          <div id="login_version">
<?php if ($show_login) { ?>
            <div class="instructions">Sign in with Google to access your own public data (<a href="#" target="_self">more info</a>)</div>
            <button class="g-signin"
              data-scope="https://www.googleapis.com/auth/plus.login"
              data-clientId="<?php echo $client_id; ?>"
              data-callback="onSignInCallback"
              data-redirecturi="postmessage"
              data-approvalprompt="force"
              data-theme="dark"
              data-width="wide"
              data-height="tall"
              data-cookiepolicy="single_host_origin">
            </button>
            <p class="smallc">(By signing in you explicitly agree that cookies will be stored on your local device,<br>to keep you logged in during this session.)</p>
<?php } ?>
          </div>
          <div class="or">or</div>
          <div id="api_version">
            <div class="instructions">Enter the ID or URL of a Google+&#8482; Profile, Page or Community (<a href="#" target="_self">more info</a>)</div>
            <form method="post" action="<?php echo $base_url; ?>" target="_self">
              <input id="userid" name="userid" type="text">
              <input id="submit" type="submit">
            </form>
          </div>
          <div class="or">or</div>
          <div id="takeout_version">
            <div class="instructions">Use your downloaded Google Takeout data (<a href="#" target="_self">more info</a>)</div>
            <div id="drop_zone" title="All your data will stay local. Nothing will be uploaded to any server.">Drop files here</div>
          </div>
          <div class="or">or</div>
          <div id="search_version">
            <div class="instructions">Enter a search term (<a href="#" target="_self">more info</a>)</div>
            <form method="post" action="<?php echo $base_url; ?>" target="_self">
              <input id="keyword" name="keyword" type="text">
              <input id="submit" type="submit">
            </form>
          </div>
          <div id="load_more" style="display: none;">
            <button id="load_500">Load more activities</button>
            <button id="load_all">Load all activities</button>
          </div>
          <div id="filter_data" style="display: none;">
            <h1>Filter activities</h1><br>
            <div id="date_range">
              Date between <input type="text" id="date_from"> and <input type="text" id="date_to">
            </div>
            <div id="date_slider"></div>
            Keyword <input type="text" id="filter_keyword"><br>
            <button id="all_activities">Reset filters</button><br>
            <span id="filtered_activities"></span>
          </div><br><br>
          <div id="sign_out" style="display: none;">
            Signed in as <a id="login_user"></a> <button id="sign_out_button">Sign out and disconnect</button>
          </div>
        </td>
        <td id="instructions">
          <div id="login_instructions" style="display: none;">
            <h1>Sign in with Google</h1><br>

            Clicking on the Sign-in button will open a Google page where you will be asked to give permission to this page to access some of your information.<br><br>
            <img src="<?php echo $base_url; ?>images/instructions_login.png" alt="Login"><br>
            Clicking on "Accept" will update this page with your profile information automatically.<br><br>
            The permission will only be used once to get your profile ID and access your public profile information. None of this data will be stored on our server.

          </div>
          <div id="api_instructions" style="display: none;">
            <h1>ID or URL of a Google+ Profile, Page or Community</h1><br>

            You can find the necessary ID by opening the Google+ profile of the person/page/community you are interested in. The URL will look something like this:<br><br>
            <a class="highlight" href="https://plus.google.com/102056575560237938284/posts">https://plus.google.com/102056575560237938284/posts</a><br><br>
            You can either copy &amp; paste the whole URL or just the long number into the input field and press "Submit". This will load the data for the person/page of your choice.<br>
          </div>
          <div id="takeout_instructions" style="display: none;">
            <h1>Using Google Takeout Data</h1><br>

            This method has the advantage that you have access to all your posts and not only to your public posts which are available through the Google+ API.
            You will need your "Stream"-data in JSON format.<br>
            To get started visit the following URL:<br>
            <a class="highlight" href="https://www.google.com/takeout/#custom:stream">https://www.google.com/takeout/#custom:stream</a><br><br>
            The important thing is that you click on "Configure" and change the activity type to "JSON" before you create the archive.<br>
            <img src="<?php echo $base_url; ?>images/instructions_takeout.png" alt="Takeout Configuration"><br>
            Once your package is ready you will have to download and extract it. Then you can drag the .json files from your file browser and drop them into the field on the left.<br><br>
            All your data will be analysed locally on your computer, nothing will be uploaded.
          </div>
          <div id="search_instructions" style="display: none;">
            <h1>Google+ Search</h1><br>
            As opposed to offering statistics for one specific person or page this will use the Google+ Search API to find posts matching your search term and analyse those.<br>
            The way the Google+ Search API works this won't find all the matching posts but I try my best to get as many posts as possible out of the API.<br>
            When no more posts can be found you will be able to restart the search after a short timeout which often produces some additional results.
          </div>
        </td>
        <td><div id="stat_types" style="display: none;">
          <h1>What are you interested in?</h1><br>
          <div class="stat_type" id="stat_overview">
            <img src="<?php echo $base_url; ?>images/stat_overview.png" alt="Overview"><br>
            Overview
          </div>
          <div class="stat_type" id="stat_locations">
            <img src="<?php echo $base_url; ?>images/stat_locations.png" alt="Locations"><br>
            Locations
          </div>
          <div class="stat_type" id="stat_charts">
            <img src="<?php echo $base_url; ?>images/stat_charts.png" alt="Charts"><br>
            Charts
          </div>
          <div class="stat_type" id="stat_popular">
            <img src="<?php echo $base_url; ?>images/stat_popular.png" alt="Popular Posts"><br>
            Popular Posts
          </div>
          <div class="stat_type" id="stat_people">
            <img src="<?php echo $base_url; ?>images/stat_people.png" alt="People"><br>
            People
          </div>
          <div class="stat_type" id="stat_photos">
            <img src="<?php echo $base_url; ?>images/stat_photos.png" alt="Photos"><br>
            Photos
          </div>
          <div class="stat_type" id="stat_posts">
            <img src="<?php echo $base_url; ?>images/stat_posts.png" alt="All Posts"><br>
            All Posts
          </div>
          <div class="stat_type" id="stat_data">
            <img src="<?php echo $base_url; ?>images/stat_data.png" alt="Raw Data Export"><br>
            Raw Data Export
          </div>
        </div>
        </td>
      </tr></table>
    </div>

    <div id="overview" class="anchor" style="display: none;"><div class="title">Overview</div><button class="recalculate">Refresh data</button></div>
    <div id="d_overview" class="contents" style="display: none;">
      <table style="width: 100%;"><tr>
        <td>
          <table>
            <tr><th colspan="4" style="text-align: center; background-color: yellow;">Total</th></tr>
            <tr><th></th><th>Total</th><th>Original</th><th>Reshared</th></tr>
            <tr><th>Posts</th><td class="stats" id="t_posts"></td><td class="stats" id="t_posts_o"></td><td class="stats" id="t_posts_r"></td></tr>
            <tr><th>Location</th><td class="stats" id="t_loc"></td><td class="stats" id="t_loc_o"></td><td class="stats" id="t_loc_r"></td></tr>
            <tr><th>Photos</th><td class="stats" id="t_photos"></td><td class="stats" id="t_photos_o"></td><td class="stats" id="t_photos_r"></td></tr>
            <tr><th>GIFs</th><td class="stats" id="t_gifs"></td><td class="stats" id="t_gifs_o"></td><td class="stats" id="t_gifs_r"></td></tr>
            <tr><th>Videos</th><td class="stats" id="t_videos"></td><td class="stats" id="t_videos_o"></td><td class="stats" id="t_videos_r"></td></tr>
            <tr><th>Links</th><td class="stats" id="t_links"></td><td class="stats" id="t_links_o"></td><td class="stats" id="t_links_r"></td></tr>
            <tr><th>Comments</th><td class="stats" id="t_comments"></td><td class="stats" id="t_comments_o"></td><td class="stats" id="t_comments_r"></td></tr>
            <tr><td class="stats noborder">per post</td><td class="stats" id="t_cpp"></td><td class="stats" id="t_cpp_o"></td><td class="stats" id="t_cpp_r"></td></tr>
            <tr><th>+1's</th><td class="stats" id="t_plusones"></td><td class="stats" id="t_plusones_o"></td><td class="stats" id="t_plusones_r"></td></tr>
            <tr><td class="stats noborder">per post</td><td class="stats" id="t_ppp"></td><td class="stats" id="t_ppp_o"></td><td class="stats" id="t_ppp_r"></td></tr>
            <tr><th>Reshares</th><td class="stats" id="t_reshares"></td><td class="stats" id="t_reshares_o"></td><td class="stats" id="t_reshares_r"></td></tr>
            <tr><td class="stats noborder">per post</td><td class="stats" id="t_rpp"></td><td class="stats" id="t_rpp_o"></td><td class="stats" id="t_rpp_r"></td></tr>
          </table>
        </td>
        <td>
          <table>
            <tr><th colspan="4" style="text-align: center; background-color: yellow;">Public</th></tr>
            <tr><th></th><th>Total</th><th>Original</th><th>Reshared</th></tr>
            <tr><th>Posts</th><td class="stats" id="tp_posts"></td><td class="stats" id="tp_posts_o"></td><td class="stats" id="tp_posts_r"></td></tr>
            <tr><th>Location</th><td class="stats" id="tp_loc"></td><td class="stats" id="tp_loc_o"></td><td class="stats" id="tp_loc_r"></td></tr>
            <tr><th>Photos</th><td class="stats" id="tp_photos"></td><td class="stats" id="tp_photos_o"></td><td class="stats" id="tp_photos_r"></td></tr>
            <tr><th>GIFs</th><td class="stats" id="tp_gifs"></td><td class="stats" id="tp_gifs_o"></td><td class="stats" id="tp_gifs_r"></td></tr>
            <tr><th>Videos</th><td class="stats" id="tp_videos"></td><td class="stats" id="tp_videos_o"></td><td class="stats" id="tp_videos_r"></td></tr>
            <tr><th>Links</th><td class="stats" id="tp_links"></td><td class="stats" id="tp_links_o"></td><td class="stats" id="tp_links_r"></td></tr>
            <tr><th>Comments</th><td class="stats" id="tp_comments"></td><td class="stats" id="tp_comments_o"></td><td class="stats" id="tp_comments_r"></td></tr>
            <tr><td class="stats noborder">per post</td><td class="stats" id="tp_cpp"></td><td class="stats" id="tp_cpp_o"></td><td class="stats" id="tp_cpp_r"></td></tr>
            <tr><th>+1's</th><td class="stats" id="tp_plusones"></td><td class="stats" id="tp_plusones_o"></td><td class="stats" id="tp_plusones_r"></td></tr>
            <tr><td class="stats noborder">per post</td><td class="stats" id="tp_ppp"></td><td class="stats" id="tp_ppp_o"></td><td class="stats" id="tp_ppp_r"></td></tr>
            <tr><th>Reshares</th><td class="stats" id="tp_reshares"></td><td class="stats" id="tp_reshares_o"></td><td class="stats" id="tp_reshares_r"></td></tr>
            <tr><td class="stats noborder">per post</td><td class="stats" id="tp_rpp"></td><td class="stats" id="tp_rpp_o"></td><td class="stats" id="tp_rpp_r"></td></tr>
          </table>
        </td>
        <td>
          <table>
            <tr><th colspan="4" style="text-align: center; background-color: yellow;">Public Communities</th></tr>
            <tr><th></th><th>Total</th><th>Original</th><th>Reshared</th></tr>
            <tr><th>Posts</th><td class="stats" id="tco_posts"></td><td class="stats" id="tco_posts_o"></td><td class="stats" id="tco_posts_r"></td></tr>
            <tr><th>Location</th><td class="stats" id="tco_loc"></td><td class="stats" id="tco_loc_o"></td><td class="stats" id="tco_loc_r"></td></tr>
            <tr><th>Photos</th><td class="stats" id="tco_photos"></td><td class="stats" id="tco_photos_o"></td><td class="stats" id="tco_photos_r"></td></tr>
            <tr><th>GIFs</th><td class="stats" id="tco_gifs"></td><td class="stats" id="tco_gifs_o"></td><td class="stats" id="tco_gifs_r"></td></tr>
            <tr><th>Videos</th><td class="stats" id="tco_videos"></td><td class="stats" id="tco_videos_o"></td><td class="stats" id="tco_videos_r"></td></tr>
            <tr><th>Links</th><td class="stats" id="tco_links"></td><td class="stats" id="tco_links_o"></td><td class="stats" id="tco_links_r"></td></tr>
            <tr><th>Comments</th><td class="stats" id="tco_comments"></td><td class="stats" id="tco_comments_o"></td><td class="stats" id="tco_comments_r"></td></tr>
            <tr><td class="stats noborder">per post</td><td class="stats" id="tco_cpp"></td><td class="stats" id="tco_cpp_o"></td><td class="stats" id="tco_cpp_r"></td></tr>
            <tr><th>+1's</th><td class="stats" id="tco_plusones"></td><td class="stats" id="tco_plusones_o"></td><td class="stats" id="tco_plusones_r"></td></tr>
            <tr><td class="stats noborder">per post</td><td class="stats" id="tco_ppp"></td><td class="stats" id="tco_ppp_o"></td><td class="stats" id="tco_ppp_r"></td></tr>
            <tr><th>Reshares</th><td class="stats" id="tco_reshares"></td><td class="stats" id="tco_reshares_o"></td><td class="stats" id="tco_reshares_r"></td></tr>
            <tr><td class="stats noborder">per post</td><td class="stats" id="tco_rpp"></td><td class="stats" id="tco_rpp_o"></td><td class="stats" id="tco_rpp_r"></td></tr>
          </table>
        </td></tr>
        <tr><td></td>
        <td>
          <table class="takeout">
            <tr><th colspan="4" style="text-align: center; background-color: yellow;">Private</th></tr>
            <tr><th></th><th>Total</th><th>Original</th><th>Reshared</th></tr>
            <tr><th>Posts</th><td class="stats" id="tpr_posts"></td><td class="stats" id="tpr_posts_o"></td><td class="stats" id="tpr_posts_r"></td></tr>
            <tr><th>Location</th><td class="stats" id="tpr_loc"></td><td class="stats" id="tpr_loc_o"></td><td class="stats" id="tpr_loc_r"></td></tr>
            <tr><th>Photos</th><td class="stats" id="tpr_photos"></td><td class="stats" id="tpr_photos_o"></td><td class="stats" id="tpr_photos_r"></td></tr>
            <tr><th>GIFs</th><td class="stats" id="tpr_gifs"></td><td class="stats" id="tpr_gifs_o"></td><td class="stats" id="tpr_gifs_r"></td></tr>
            <tr><th>Videos</th><td class="stats" id="tpr_videos"></td><td class="stats" id="tpr_videos_o"></td><td class="stats" id="tpr_videos_r"></td></tr>
            <tr><th>Links</th><td class="stats" id="tpr_links"></td><td class="stats" id="tpr_links_o"></td><td class="stats" id="tpr_links_r"></td></tr>
            <tr><th>Comments</th><td class="stats" id="tpr_comments"></td><td class="stats" id="tpr_comments_o"></td><td class="stats" id="tpr_comments_r"></td></tr>
            <tr><td class="stats noborder">per post</td><td class="stats" id="tpr_cpp"></td><td class="stats" id="tpr_cpp_o"></td><td class="stats" id="tpr_cpp_r"></td></tr>
            <tr><th>+1's</th><td class="stats" id="tpr_plusones"></td><td class="stats" id="tpr_plusones_o"></td><td class="stats" id="tpr_plusones_r"></td></tr>
            <tr><td class="stats noborder">per post</td><td class="stats" id="tpr_ppp"></td><td class="stats" id="tpr_ppp_o"></td><td class="stats" id="tpr_ppp_r"></td></tr>
            <tr><th>Reshares</th><td class="stats" id="tpr_reshares"></td><td class="stats" id="tpr_reshares_o"></td><td class="stats" id="tpr_reshares_r"></td></tr>
            <tr><td class="stats noborder">per post</td><td class="stats" id="tpr_rpp"></td><td class="stats" id="tpr_rpp_o"></td><td class="stats" id="tpr_rpp_r"></td></tr>
          </table>
        </td>
        <td>
          <table class="takeout">
            <tr><th colspan="4" style="text-align: center; background-color: yellow;">Private Communities</th></tr>
            <tr><th></th><th>Total</th><th>Original</th><th>Reshared</th></tr>
            <tr><th>Posts</th><td class="stats" id="tpc_posts"></td><td class="stats" id="tpc_posts_o"></td><td class="stats" id="tpc_posts_r"></td></tr>
            <tr><th>Location</th><td class="stats" id="tpc_loc"></td><td class="stats" id="tpc_loc_o"></td><td class="stats" id="tpc_loc_r"></td></tr>
            <tr><th>Photos</th><td class="stats" id="tpc_photos"></td><td class="stats" id="tpc_photos_o"></td><td class="stats" id="tpc_photos_r"></td></tr>
            <tr><th>GIFs</th><td class="stats" id="tpc_gifs"></td><td class="stats" id="tpc_gifs_o"></td><td class="stats" id="tpc_gifs_r"></td></tr>
            <tr><th>Videos</th><td class="stats" id="tpc_videos"></td><td class="stats" id="tpc_videos_o"></td><td class="stats" id="tpc_videos_r"></td></tr>
            <tr><th>Links</th><td class="stats" id="tpc_links"></td><td class="stats" id="tpc_links_o"></td><td class="stats" id="tpc_links_r"></td></tr>
            <tr><th>Comments</th><td class="stats" id="tpc_comments"></td><td class="stats" id="tpc_comments_o"></td><td class="stats" id="tpc_comments_r"></td></tr>
            <tr><td class="stats noborder">per post</td><td class="stats" id="tpc_cpp"></td><td class="stats" id="tpc_cpp_o"></td><td class="stats" id="tpc_cpp_r"></td></tr>
            <tr><th>+1's</th><td class="stats" id="tpc_plusones"></td><td class="stats" id="tpc_plusones_o"></td><td class="stats" id="tpc_plusones_r"></td></tr>
            <tr><td class="stats noborder">per post</td><td class="stats" id="tpc_ppp"></td><td class="stats" id="tpc_ppp_o"></td><td class="stats" id="tpc_ppp_r"></td></tr>
            <tr><th>Reshares</th><td class="stats" id="tpc_reshares"></td><td class="stats" id="tpc_reshares_o"></td><td class="stats" id="tpc_reshares_r"></td></tr>
            <tr><td class="stats noborder">per post</td><td class="stats" id="tpc_rpp"></td><td class="stats" id="tpc_rpp_o"></td><td class="stats" id="tpc_rpp_r"></td></tr>
          </table>
        </td>
      </tr></table><br>
    </div>
    <div id="locations" class="anchor" style="display: none;"><div class="title">Locations</div><button class="recalculate">Refresh data</button></div>
    <div id="d_locations" class="contents" style="display: none;">
      <div id="map_canvas"></div>
    </div>
    <div id="charts" class="anchor" style="display: none;"><div class="title">Charts</div><button class="recalculate">Refresh data</button></div>
    <div id="d_charts" class="contents" style="display: none;">
      <p class="smalll">Note: All times are based on your local timezone.</p>
      <table class="smalll"><tr>
        <td>Type: Total <input type="checkbox" id="chk_total" name="chk_total" value="chk_total" checked> / Original <input type="checkbox" id="chk_original" name="chk_original" value="chk_original" checked> / Reshared <input type="checkbox" id="chk_reshared" name="chk_reshared" value="chk_reshared"></td>
        <td class="smallr">Audience: Total <input type="checkbox" id="chk_all" name="chk_all" value="chk_all" checked> / Public <input type="checkbox" id="chk_public" name="chk_public" value="chk_public"> / Public Communities <input type="checkbox" id="chk_community" name="chk_community" value="chk_community"> <span class="takeout" style="vertical-align: top;">/ Private <input type="checkbox" id="chk_private" name="chk_private" value="chk_private"> / Private Communities <input type="checkbox" id="chk_private_communities" name="chk_private_communities" value="chk_private_communities"></span></td>
      </tr><tr><td colspan="2">
        Values: Posts <input type="checkbox" id="chk_posts" name="chk_posts" value="chk_posts" checked>
        / Location <input type="checkbox" id="chk_location" name="chk_location" value="chk_location">
        / Photos <input type="checkbox" id="chk_photos" name="chk_photos" value="chk_photos">
        / GIFs <input type="checkbox" id="chk_gifs" name="chk_gifs" value="chk_gifs">
        / Videos <input type="checkbox" id="chk_videos" name="chk_videos" value="chk_videos">
        / Links <input type="checkbox" id="chk_links" name="chk_links" value="chk_links">
        / Comments <input type="checkbox" id="chk_comments" name="chk_comments" value="chk_comments">
        / CpP <input type="checkbox" id="chk_cpp" name="chk_cpp" value="chk_cpp">
        / +1's <input type="checkbox" id="chk_plusones" name="chk_plusones" value="chk_plusones">
        / PpP <input type="checkbox" id="chk_ppp" name="chk_ppp" value="chk_ppp">
        / Reshares <input type="checkbox" id="chk_reshares" name="chk_reshares" value="chk_reshares">
        / RpP <input type="checkbox" id="chk_rpp" name="chk_rpp" value="chk_rpp">
      </td></tr></table>
      <div id="chart_warning" style="font-weight:bold;">No values selected.<br><br></div>
      <div id="day_chart"></div>
      <div id="weekday_chart"></div>
      <div id="hour_chart"></div>
    </div>

    <div id="popular" class="anchor" style="display: none;"><div class="title">Popular Posts</div><button class="recalculate">Refresh data</button></div>
    <div id="d_popular" class="contents" style="display: none;"></div>
    <div id="people" class="anchor" style="display: none;"><div class="title">People</div><button class="recalculate">Refresh data</button></div>
    <div id="d_people" class="contents" style="display: none;">
      <table class="followers" style="display: none;">
        <tr>
          <th title="People who commented on posts" class="takeout">Commenters</th>
          <th title="People who reshared posts" class="takeout">Resharers</th>
          <th title="People who +1'd posts" class="takeout" id="plusoners_head">+1'ers</th>
          <th title="People whose posts have been reshared">Reshared</th>
        </tr>
        <tr>
          <td id="commenters" class="takeout"></td>
          <td id="resharers" class="takeout"></td>
          <td id="plusoners" class="takeout"></td>
          <td id="reshared"></td>
        </tr>
      </table>
    </div>
    <div id="photos" class="anchor" style="display: none;"><div class="title">Photos</div><button class="recalculate">Refresh data</button></div>
    <div id="d_photos" class="contents" style="display: none;"></div>
    <div id="posts" class="anchor" style="display: none;"><div class="title">Posts</div><button class="recalculate">Refresh data</button></div>
    <div id="d_posts" class="contents" style="display: none;">
      You can sort the table by clicking on the column headers.<br><br>
      Filter options:
      <table class="filter_table">
        <tr>
          <td colspan="5" style="text-align: left;">Audience: Public <input type="checkbox" id="posts_public" name="posts_public" value="posts_public" checked> /
            Public Communities <input type="checkbox" id="posts_community" name="posts_community" value="posts_community" checked>
            <span class="takeout"> / Private <input type="checkbox" id="posts_private" name="posts_private" value="posts_private" checked> /
            Private Communities <input type="checkbox" id="posts_private_community" name="posts_private_community" value="posts_private_community" checked></span>
          </td>
        </tr>
        <tr>
          <td>Type: Original <input type="checkbox" id="posts_original" name="posts_original" value="posts_original" checked> /
                Reshared <input type="checkbox" id="posts_reshared" name="posts_reshared" value="posts_reshared" checked></td>
          <td>Comments: With <input type="checkbox" id="posts_comments" name="posts_comments" value="posts_comments" checked> /
                        Without <input type="checkbox" id="posts_comments_wo" name="posts_comments_wo" value="posts_comments_wo" checked></td>
          <td>+1's: With <input type="checkbox" id="posts_plusones" name="posts_plusones" value="posts_plusones" checked> /
                    Without <input type="checkbox" id="posts_plusones_wo" name="posts_plusones_wo" value="posts_plusones_wo" checked></td>
          <td colspan="2" style="text-align: left;">Reshares: With <input type="checkbox" id="posts_reshares" name="posts_reshares" value="posts_reshares" checked> /
                        Without <input type="checkbox" id="posts_reshares_wo" name="posts_reshares_wo" value="posts_reshares_wo" checked></td>
        </tr>
        <tr>
          <td>Location: With <input type="checkbox" id="posts_location" name="posts_location" value="posts_location" checked> /
                    Without <input type="checkbox" id="posts_location_wo" name="posts_location_wo" value="posts_location_wo" checked></td>
          <td>Photos: With <input type="checkbox" id="posts_photos" name="posts_photos" value="posts_photos" checked> /
                  Without <input type="checkbox" id="posts_photos_wo" name="posts_photos_wo" value="posts_photos_wo" checked></td>
          <td>GIFs: With <input type="checkbox" id="posts_gifs" name="posts_gifs" value="posts_gifs" checked> /
                    Without <input type="checkbox" id="posts_gifs_wo" name="posts_gifs_wo" value="posts_gifs_wo" checked></td>
          <td>Videos: With <input type="checkbox" id="posts_videos" name="posts_videos" value="posts_videos" checked> /
                      Without <input type="checkbox" id="posts_videos_wo" name="posts_videos_wo" value="posts_videos_wo" checked></td>
          <td>Links: With <input type="checkbox" id="posts_links" name="posts_links" value="posts_links" checked> /
                     Without <input type="checkbox" id="posts_links_wo" name="posts_links_wo" value="posts_links_wo" checked></td>
        </tr>
      </table><br>
      <table class="sortable" id="posts_table">
        <thead>
          <tr>
            <th>Date</th>
            <th class="sorttable_numeric" title="Audience: PU = Public, CO = Public Community, PR = Private, PC = Private Community">A</th>
            <th class="sorttable_numeric" title="Comments">C</th>
            <th class="sorttable_numeric" title="Reshares">R</th>
            <th class="sorttable_numeric" title="+1's">+1</th>
            <th class="sorttable_numeric" title="Post Length">L</th>
            <th>Post</th></tr>
        </thead>
        <tbody>
        </tbody>
      </table>
    </div>
    <div id="data" class="anchor" style="display: none;"><div class="title">Raw Data</div><button class="recalculate">Refresh data</button></div>
    <div id="d_data" class="contents" style="display: none;">
      <span id="data_download"></span><br><br>
      <table id="data_table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Day</th>
            <th>Weekday</th>
            <th>Hour</th>
            <th>Audience</th>
            <th>Org</th>
            <th>Reshare</th>
            <th>Location</th>
            <th>Photos</th>
            <th>GIFs</th>
            <th>Videos</th>
            <th>Links</th>
            <th>C</th>
            <th>R</th>
            <th>+1</th>
            <th>Length</th>
            <th>Link</th>
            <th>Poster</th>
            <th>Poster ID</th>
            <th>Org Poster</th>
            <th>Org Poster ID</th>
            <th>Annotation</th>
            <th>Post</th>
          </tr>
        </thead>
        <tbody>
        </tbody>
      </table>
    </div>
  </div>
  <div id="footer" class="footer_data">
    <a href="https://plus.google.com/105696887942257432718?rel=publisher" style="display: none;"></a>
    <p class="smallr">
      Google+ is a trademark of Google Inc. Use of this trademarks is subject to <a href="http://www.google.com/permissions/index.html">Google Permissions</a>.<br>
      This site is not affiliated with, sponsored by, or endorsed by <a href="http://www.google.com/">Google Inc</a>.<br>
      Programming by <a href="https://plus.google.com/112336147904981294875" rel="author">Gerwin Sturm</a>, <a href="http://www.foldedsoft.at/">FoldedSoft e.U.</a><br><br>
    </p>
  </div>
</body>
</html>

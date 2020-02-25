<?php
namespace HootsuiteTest;

include_once("autoload.php");

use HootSuite\HootsuiteManager;

$token = "iw-jMmhe44r1yHUw7UmTb93qoi5ZOQ9dZUrxrcJfYjg.VWwuqOUT1Rv8TsFy7camcr18mb32UqKfIFG9Z7HSgpQ";
$manager = new HootsuiteManager($token, true);
$manager->setHookUrls("https://hootsuite-testing-mysite.herokuapp.com");
// $manager->getSocials();
$manager->postOne(1);
// $manager->deletePostWithPosted();
// $manager->deletePost(1);
// $manager->deletePostWithPosted("5893790943");
// $manager->deletePostWithPosted("5893800516");
echo "---------------------------  STEP 1 ---------------------------------<br/>";
// $manager->getMediaStatus("aHR0cHM6Ly9ob290c3VpdGUtdmlkZW8uczMuYW1hem9uYXdzLmNvbS9wcm9kdWN0aW9uLzIxNTM5MzM5XzExZjBhMGY1LTRiMmUtNGU5NS1hOGFlLWRlM2IzYzMyMDZmYi5wbmc=");

echo "---------------------------  STEP 2 ---------------------------------<br/>";

// $manager->uploadFile("https://hootsuite-video.s3.amazonaws.com/production/21539339_11f0a0f5-4b2e-4e95-a8ae-de3b3c3206fb.png?AWSAccessKeyId=AKIAIM7ASX2JTE3ZFAAA&Expires=1582581136&Signature=1YxoUEBFAv5rTuT3pe1io7aR9zk%3D");

echo "---------------------------  STEP 3 ---------------------------------<br/>";
// $manager->getMediaStatus("aHR0cHM6Ly9ob290c3VpdGUtdmlkZW8uczMuYW1hem9uYXdzLmNvbS9wcm9kdWN0aW9uLzIxNTM5MzM5XzExZjBhMGY1LTRiMmUtNGU5NS1hOGFlLWRlM2IzYzMyMDZmYi5wbmc=");
echo "---------------------------  STEP 4 ---------------------------------<br/>";
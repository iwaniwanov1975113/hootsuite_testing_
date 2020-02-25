<?php
namespace HootSuite;

use HootSuite\TableManager;
use HootSuite\Connection;
use HootSuite\CurlRequest;
use HootSuite\Options\Messages\ScheduleMessage;
use HootSuite\Options\Messages\DeleteMessage;

use HootSuite\Options\SocialProfile\SocialProfiles;
use HootSuite\Options\Media\CreateUrl;
use HootSuite\Options\Media\MediaStatus;

class HootsuiteManager
{
    protected $_connection;
    protected $_dbmng;
    protected $_hookurls;

    public function __construct($token = NULL, $debugMode = false) 
    {
        $curlRequest = new CurlRequest();
        $this->_connection = new Connection($token, $curlRequest, $debugMode);
        $this->_dbmng = new TableManager();
    }

    public function setHookurls($hookurls)
    {
        $this->_hookurls = $hookurls;
    }

    public function postAll()
    {
        $medias = $this->_dbmng->getMedias(['posted_id'=>'']);
        foreach ($medias as $media) {
            postOne($media['id']);
        }        
    }

    public function postOne($draft_id)
    {
        $medias = $this->_dbmng->getMedias(['id'=>$draft_id]);
        $media = [];
        if(sizeof($medias)>0)
            $media = $medias[0];
        else
            throw new \Exception("the draft id does not exist:{$draft_id}", 1);

        $sizeBytes = filesize($media['media_path']);
        // $sizeBytes = 8036821;
        $option = new CreateUrl();
        $mimeType = $media['mime_type'];
        $mimeType = str_replace("\\", "", $mimeType);
        $option->setMimeType($mimeType);
        $option->setSizeBytes($sizeBytes);

        $urls_json = json_decode($this->_connection->request($option));
        
        echo "<br/>Media ID: ".$urls_json->data->id."<br/><br/>";

        $this->_connection->uploadMedia(
            $urls_json->data->uploadUrl, 
            $media['media_path'], 
            $mimeType,
            $sizeBytes
        );
        

        $option = new ScheduleMessage();
        $option->setText($media['title']);
        
        $sheduled_time = new \DateTime($media['scheduled_time']);
        $option->setScheduledSendTime($sheduled_time->format('Y-m-d\TH:i:s\Z'));
        
        $option->setSocialProfileIds([$media['socialid']]);
        $option->setWebhookUrls([$this->_hookurls]);

        //only can use this after purchase
        // $option->setTags(explode(",", $media['tags']));


        $option->setMedia([[
            "id"=>$urls_json->data->id,
            "videoOptions"=>[
                "facebook"=>[
                    "title"     =>$media['title'], 
                    "category"  => "ENTERTAINMENT",
                ]
            ]
        ]]);

        $result = $this->_connection->request($option);
        $json = json_decode($result);

        
        $this->_dbmng->postedMedia($draft_id, $json->data[0]->id);
    }

    public function uploadFile($url)
    {
        $sizeBytes = filesize('img.png');
        $this->_connection->uploadMedia(
            $url, 
            'img.png', 
            'image/png',
            $sizeBytes
        );
    }

    public function deletePost($draft_id)
    {
        $draft = $this->_dbmng->getMedias(['id'=>$draft_id]);
        $posted_id = sizeof($draft)>0?$draft[0]['posted_id']:'';
        
        if(!! $posted_id)
            throw new \Exception("Delete processing is failed. Your message is not posted.", 1);

        $option = new DeleteMessage();
        $option->setMessageId($posted_id);
        $this->_connection->request($option);
        return $posted_id;
    }

    public function deletePostWithPosted($posted_id)
    {
        $option = new DeleteMessage();
        $option->setMessageId($posted_id);
        $this->_connection->request($option);
        return $posted_id;
    }

    public function getState($params)
    {
        if(isset($params['draft_id']))
            echo "";
        if(isset($params['posted_id']))
            echo "";
    }

    public function getSocials()
    {
        $option = new SocialProfiles();
        return $this->_connection->request($option);
    }

    public function getMediaStatus($mediaid)
    {
        $option = new MediaStatus();
        $option->setMediaId($mediaid);
        return $this->_connection->request($option);
    }
}

<?php

/**
 * This class is used to connect to the instagram API
 */
class NNR_Instagram_v1 {

	/**
	 * authorize_url
	 *
	 * (default value: 'https://api.instagram.com/oauth/authorize/')
	 *
	 * @var string
	 * @access private
	 */
	private $authorize_url = 'https://api.instagram.com/oauth/authorize/';

	/**
	 * access_token_url
	 *
	 * (default value: 'https://api.instagram.com/oauth/access_token')
	 *
	 * @var string
	 * @access private
	 */
	private $access_token_url = 'https://api.instagram.com/oauth/access_token';

	/**
	 * client_id
	 *
	 * (default value: '')
	 *
	 * @var string
	 * @access private
	 */
	private $client_id = '';

	/**
	 * client_secret
	 *
	 * (default value: '')
	 *
	 * @var string
	 * @access private
	 */
	private $client_secret = '';

	/**
	 * access_token
	 *
	 * (default value: '')
	 *
	 * @var string
	 * @access private
	 */
	private $access_token = '';

	/**
	 * Create a new instance
	 *
	 * @access public
	 * @param mixed $client_id
	 * @param mixed $client_secret
	 * @return void
	 */
	function __construct($client_id, $client_secret, $access_token = '') {

		$this->client_id = $client_id;
		$this->client_secret = $client_secret;
		$this->access_token = $access_token;
	}

	/**
	 * Get the connection url
	 *
	 * @access public
	 * @param mixed $redirect_url
	 * @return void
	 */
	function connect_url($redirect_url) {
		return $this->authorize_url . '?client_id=' . $this->client_id . '&redirect_uri=' . urlencode($redirect_url) . '&response_type=code';
	}

	/**
	 * Get an access token
	 *
	 * @access public
	 * @param mixed $redirect_url
	 * @return array $response
	 */
	function get_access_token($redirect_url) {

		$ch = curl_init();

	    curl_setopt($ch, CURLOPT_URL, $this->access_token_url);
		curl_setopt($ch, CURLOPT_POST, TRUE);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array(
			'client_id' 	=> $this->client_id,
			'client_secret' => $this->client_secret,
		    'redirect_uri' 	=> $redirect_url,
		    'code' 			=> $_GET['code'],
		    'grant_type'	=> 'authorization_code',
		));

	    $response = curl_exec($ch);
	    curl_close($ch);

	    $response = json_decode($response);

	    if ( $response->access_token ) {
			$this->access_token = $response->access_token;
	    }

	    return $response;
	}

	/**
	 * Get recent media for a specific user
	 *
	 * @access public
	 * @param mixed $user_id
	 * @param int $count (default: 5)
	 * @return array $response
	 */
	function get_recent_media($user_id, $count = 5, $min_id = null, $max_id = null) {

		$ids = '';

		if ( isset($min_id) ) {
			$ids .= '&min_id=' . $min_id;
		}

		if ( isset($max_id) ) {
			$ids .= '&max_id=' . $max_id;
		}

		return json_decode($this->get_request("https://api.instagram.com/v1/users/" . $user_id . "/media/recent/?access_token=" . $this->access_token . "&count=" . $count . $ids));
	}

	/**
	 * Get recent media for a specific user
	 *
	 * @access public
	 * @param mixed $username
	 * @param int $count (default: 5)
	 * @return void
	 */
	function get_recent_media_from_username($username, $count = 5, $min_id = null, $max_id = null) {

		return $this->get_recent_media($this->get_user_id_from_name($username), $count, $min_id, $max_id);
	}

	/**
	 * Get most recent media based on tag
	 *
	 * @access public
	 * @param mixed $tag
	 * @param int $count (default: 5)
	 * @param mixed $min_tag_id (default: null)
	 * @param mixed $max_tag_id (default: null)
	 * @return void
	 */
	function get_recent_media_from_tag($tag, $count = 5, $min_tag_id = null, $max_tag_id = null) {

		$tag_ids = '';

		if ( isset($min_tag_id) ) {
			$tag_ids .= '&min_tag_id=' . $min_tag_id;
		}

		if ( isset($max_tag_id) ) {
			$tag_ids .= '&max_tag_id=' . $max_tag_id;
		}

		return json_decode($this->get_request("https://api.instagram.com/v1/tags/" . $tag . "/media/recent/?access_token=" . $this->access_token . "&count=" . $count . $tag_ids));

	}

	/**
	 * Get the user info from there username
	 *
	 * @access public
	 * @param mixed $user
	 * @return void
	 */
	function get_user_info($query) {

		return json_decode($this->get_request("https://api.instagram.com/v1/users/search?q=" . $query . "&access_token=" . $this->access_token . "&count=1"));
	}

	/**
	 * Get the user ID from a username
	 *
	 * @access public
	 * @param mixed $query
	 * @return void
	 */
	function get_user_id_from_name($query) {

		$user = $this->get_user_info($query);

		if ( isset($user->data[0]->id) ) {
			return $user->data[0]->id;
		}

		return null;
	}

	/**
	 * Generate the links for tags
	 *
	 * @access public
	 * @param mixed $text
	 * @return void
	 */
	function generate_links($text) {

		$text = preg_replace('/#(\w+)/', ' <a href="https://instagram.com/explore/tags/$1" target="_blank">#$1</a>', $text);
		$text = preg_replace('/@(\w+)/', ' <a href="https://instagram.com/$1" target="_blank">@$1</a>', $text);

		return $text;
	}

	/**
	 * Get the link for the user
	 *
	 * @access public
	 * @param mixed $username
	 * @return void
	 */
	function get_user_link($username) {
		return '<a href="https://instagram.com/' . $username . '" target="_blank">' . $username . '</a>';
	}

	/**
	 * Get the link for a tag
	 *
	 * @access public
	 * @param mixed $tag
	 * @return void
	 */
	function get_tag_link($tag) {
		return '<a href="https://instagram.com/explore/tags/' . $tag . '" target="_blank">#' . $tag . '</a>';
	}

	/**
	 * Perform a GET request
	 *
	 * @access public
	 * @param mixed $url
	 * @return void
	 */
	function get_request($url) {

		$curl = curl_init();
		curl_setopt_array($curl, array(
		    CURLOPT_RETURNTRANSFER 	=> 1,
		    CURLOPT_URL 			=> $url,
		));

		$response = curl_exec($curl);

		curl_close($curl);

		return $response;

	}

}
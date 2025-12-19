<?php

/*

Proxmox VE APIv2 (PVE2) Client - PHP Class (Token Authentication)

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
the Software, and to permit persons to whom the Software is furnished to do so,
subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

class PVE2_Exception extends RuntimeException
{
}

class PVE2_API
{
	protected $hostname;
	protected $api_token_id;
	protected $api_token_secret;
	protected $port;
	protected $verify_ssl;

	protected $cluster_node_list = null;

	/**
	 * Constructor avec authentification par API Token
	 * 
	 * @param string $hostname Nom d'hôte ou IP du serveur Proxmox
	 * @param string $api_token_id ID du token (format: user@realm!tokenid)
	 * @param string $api_token_secret Secret du token (UUID)
	 * @param int $port Port du serveur (défaut: 8006)
	 * @param bool $verify_ssl Vérifier le certificat SSL (défaut: false)
	 */
	public function __construct($hostname, $api_token_id, $api_token_secret, $port = 8006, $verify_ssl = false)
	{
		if (empty($hostname) || empty($api_token_id) || empty($api_token_secret) || empty($port)) {
			throw new PVE2_Exception("Hostname/API Token ID/API Token Secret/Port required for PVE2_API object constructor.", 1);
		}

		// Check hostname resolves.
		if (gethostbyname($hostname) == $hostname && !filter_var($hostname, FILTER_VALIDATE_IP)) {
			throw new PVE2_Exception("Cannot resolve {$hostname}.", 2);
		}

		// Check port is between 1 and 65535.
		if (!filter_var($port, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]])) {
			throw new PVE2_Exception("Port must be an integer between 1 and 65535.", 6);
		}

		// Check that verify_ssl is boolean.
		if (!is_bool($verify_ssl)) {
			throw new PVE2_Exception("verify_ssl must be boolean.", 7);
		}

		$this->hostname = $hostname;
		$this->api_token_id = $api_token_id;
		$this->api_token_secret = $api_token_secret;
		$this->port = $port;
		$this->verify_ssl = $verify_ssl;

		// Charger la liste des nœuds au démarrage
		$this->reload_node_list();
	}

	/**
	 * Vérifie la validité du token en testant une requête simple
	 * 
	 * @return bool True si le token est valide, false sinon
	 */
	public function check_token()
	{
		try {
			$version = $this->get("/version");
			return ($version !== false);
		} catch (Exception $e) {
			return false;
		}
	}

	/*
	 * object action (string action_path, string http_method[, array put_post_parameters])
	 * This method is responsible for the general cURL requests to the JSON API,
	 * and sits behind the abstraction layer methods get/put/post/delete etc.
	 */
	private function action($action_path, $http_method, $put_post_parameters = null)
	{
		// Check if we have a prefixed / on the path, if not add one.
		if (substr($action_path, 0, 1) != "/") {
			$action_path = "/" . $action_path;
		}

		// Prepare cURL resource.
		$prox_ch = curl_init();
		curl_setopt($prox_ch, CURLOPT_URL, "https://{$this->hostname}:{$this->port}/api2/json{$action_path}");

		$http_headers = array();
		// Utilisation de l'API Token pour l'authentification
		$http_headers[] = "Authorization: PVEAPIToken={$this->api_token_id}={$this->api_token_secret}";

		// Lets decide what type of action we are taking...
		switch ($http_method) {
			case "GET":
				// Nothing extra to do.
				break;
			case "PUT":
				curl_setopt($prox_ch, CURLOPT_CUSTOMREQUEST, "PUT");

				// Set "POST" data.
				$action_postfields_string = http_build_query($put_post_parameters);
				curl_setopt($prox_ch, CURLOPT_POSTFIELDS, $action_postfields_string);
				unset($action_postfields_string);
				break;
			case "POST":
				curl_setopt($prox_ch, CURLOPT_POST, true);

				// Set POST data.
				$action_postfields_string = http_build_query($put_post_parameters);
				curl_setopt($prox_ch, CURLOPT_POSTFIELDS, $action_postfields_string);
				unset($action_postfields_string);
				break;
			case "DELETE":
				curl_setopt($prox_ch, CURLOPT_CUSTOMREQUEST, "DELETE");
				// No "POST" data required, the delete destination is specified in the URL.
				break;
			default:
				throw new PVE2_Exception("Error - Invalid HTTP Method specified.", 5);
		}

		// Add HTTP headers for all requests
		curl_setopt($prox_ch, CURLOPT_HTTPHEADER, $http_headers);
		curl_setopt($prox_ch, CURLOPT_HEADER, true);
		curl_setopt($prox_ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($prox_ch, CURLOPT_SSL_VERIFYPEER, $this->verify_ssl);
		curl_setopt($prox_ch, CURLOPT_SSL_VERIFYHOST, $this->verify_ssl ? 2 : 0);

		$action_response = curl_exec($prox_ch);
		$curl_error = curl_error($prox_ch);

		curl_close($prox_ch);
		unset($prox_ch);

		if (!$action_response) {
			error_log("cURL Error: {$curl_error}");
			return false;
		}

		$split_action_response = explode("\r\n\r\n", $action_response, 2);
		$header_response = $split_action_response[0];
		$body_response = isset($split_action_response[1]) ? $split_action_response[1] : '';
		$action_response_array = json_decode($body_response, true);

		unset($action_response);

		// Parse response, confirm HTTP response code etc.
		$split_headers = explode("\r\n", $header_response);
		if (substr($split_headers[0], 0, 9) == "HTTP/1.1 ") {
			$split_http_response_line = explode(" ", $split_headers[0]);
			if ($split_http_response_line[1] == "200") {
				if ($http_method == "PUT") {
					return true;
				} else {
					return isset($action_response_array['data']) ? $action_response_array['data'] : false;
				}
			} else {
				error_log("This API Request Failed.\n" .
					"HTTP Response - {$split_http_response_line[1]}\n" .
					"HTTP Error - {$split_headers[0]}");
				return false;
			}
		} else {
			error_log("Error - Invalid HTTP Response.\n" . var_export($split_headers, true));
			return false;
		}
	}

	/*
	 * array reload_node_list ()
	 * Returns the list of node names as provided by /api2/json/nodes.
	 * We need this for future get/post/put/delete calls.
	 * ie. $this->get("nodes/XXX/status"); where XXX is one of the values from this return array.
	 */
	public function reload_node_list()
	{
		$node_list = $this->get("/nodes");
		if (count($node_list) > 0) {
			$nodes_array = array();
			foreach ($node_list as $node) {
				$nodes_array[] = $node['node'];
			}
			$this->cluster_node_list = $nodes_array;
			return true;
		} else {
			error_log(" Empty list of nodes returned in this cluster.");
			return false;
		}
	}

	/*
	 * array get_node_list ()
	 *
	 */
	public function get_node_list()
	{
		// We run this if we haven't queried for cluster nodes as yet, and cache it in the object.
		if ($this->cluster_node_list == null) {
			if ($this->reload_node_list() === false) {
				return false;
			}
		}

		return $this->cluster_node_list;
	}

	/*
	 * bool|int get_next_vmid ()
	 * Get Last VMID from a Cluster or a Node
	 * returns a VMID, or false if not found.
	 */
	public function get_next_vmid()
	{
		$vmid = $this->get("/cluster/nextid");
		if ($vmid == null) {
			return false;
		} else {
			return $vmid;
		}
	}

	/*
	 * array get_vms ()
	 * Get List of all vms
	 */
	public function get_vms()
	{
		$node_list = $this->get_node_list();
		$result = [];
		if (count($node_list) > 0) {
			foreach ($node_list as $node_name) {
				$vms_list = $this->get("nodes/" . $node_name . "/qemu/");
				if (count($vms_list) > 0) {
					$key_values = array_column($vms_list, 'vmid');
					array_multisort($key_values, SORT_ASC, $vms_list);
					foreach ($vms_list as &$row) {
						$row['node'] = $node_name;
					}
					$result = array_merge($result, $vms_list);
				}
			}
			if (count($result) > 0) {
				return $result;
			} else {
				error_log(" Empty list of vms returned in this cluster.");
				return false;
			}
		} else {
			error_log(" Empty list of nodes returned in this cluster.");
			return false;
		}
	}

	/*
	 * bool|int start_vm ($node,$vmid)
	 * Start specific vm
	 */
	public function start_vm($node, $vmid)
	{
		if (isset($vmid) && isset($node)) {
			$parameters = array(
				"vmid" => $vmid,
				"node" => $node,
			);
			$url = "/nodes/" . $node . "/qemu/" . $vmid . "/status/start";
			$post = $this->post($url, $parameters);
			if ($post) {
				error_log("Started vm " . $vmid . "");
				return true;
			} else {
				error_log("Error starting vm " . $vmid . "");
				return false;
			}
		} else {
			error_log("no vm or node valid");
			return false;
		}
	}

	/*
	 * bool|int shutdown_vm ($node,$vmid)
	 * Gracefully shutdown specific vm
	 */
	public function shutdown_vm($node, $vmid)
	{
		if (isset($vmid) && isset($node)) {
			$parameters = array(
				"vmid" => $vmid,
				"node" => $node,
				"timeout" => 60,
			);
			$url = "/nodes/" . $node . "/qemu/" . $vmid . "/status/shutdown";
			$post = $this->post($url, $parameters);
			if ($post) {
				error_log("Shutdown vm " . $vmid . "");
				return true;
			} else {
				error_log("Error shutting down vm " . $vmid . "");
				return false;
			}
		} else {
			error_log("no vm or node valid");
			return false;
		}
	}

	/*
	 * bool|int stop_vm ($node,$vmid)
	 * Force stop specific vm
	 */
	public function stop_vm($node, $vmid)
	{
		if (isset($vmid) && isset($node)) {
			$parameters = array(
				"vmid" => $vmid,
				"node" => $node,
				"timeout" => 60,
			);
			$url = "/nodes/" . $node . "/qemu/" . $vmid . "/status/stop";
			$post = $this->post($url, $parameters);
			if ($post) {
				error_log("Stopped vm " . $vmid . "");
				return true;
			} else {
				error_log("Error stopping vm " . $vmid . "");
				return false;
			}
		} else {
			error_log("no vm or node valid");
			return false;
		}
	}

	/*
	 * bool|int resume_vm ($node,$vmid)
	 * Resume from suspend specific vm
	 */
	public function resume_vm($node, $vmid)
	{
		if (isset($vmid) && isset($node)) {
			$parameters = array(
				"vmid" => $vmid,
				"node" => $node,
			);
			$url = "/nodes/" . $node . "/qemu/" . $vmid . "/status/resume";
			$post = $this->post($url, $parameters);
			if ($post) {
				error_log("Resumed vm " . $vmid . "");
				return true;
			} else {
				error_log("Error resuming vm " . $vmid . "");
				return false;
			}
		} else {
			error_log("no vm or node valid");
			return false;
		}
	}

	/*
	 * bool|int suspend_vm ($node,$vmid)
	 * Suspend specific vm
	 */
	public function suspend_vm($node, $vmid)
	{
		if (isset($vmid) && isset($node)) {
			$parameters = array(
				"vmid" => $vmid,
				"node" => $node,
			);
			$url = "/nodes/" . $node . "/qemu/" . $vmid . "/status/suspend";
			$post = $this->post($url, $parameters);
			if ($post) {
				error_log("Suspended vm " . $vmid . "");
				return true;
			} else {
				error_log("Error suspending vm " . $vmid . "");
				return false;
			}
		} else {
			error_log("no vm or node valid");
			return false;
		}
	}

	/*
	 * bool|int clone_vm ($node,$vmid)
	 * Create fullclone of vm
	 */
	public function clone_vm($node, $vmid)
	{
		if (isset($vmid) && isset($node)) {
			$lastid = $this->get_next_vmid();
			$parameters = array(
				"vmid" => $vmid,
				"node" => $node,
				"newid" => $lastid,
				"full" => true,
			);
			$url = "/nodes/" . $node . "/qemu/" . $vmid . "/clone";
			$post = $this->post($url, $parameters);
			if ($post) {
				error_log("Cloned vm " . $vmid . " to " . $lastid . "");
				return true;
			} else {
				error_log("Error cloning vm " . $vmid . " to " . $lastid . "");
				return false;
			}
		} else {
			error_log("no vm or node valid");
			return false;
		}
	}

	/*
	 * bool|int snapshot_vm ($node,$vmid,$snapname = NULL)
	 * Create snapshot of vm
	 */
	public function snapshot_vm($node, $vmid, $snapname = NULL)
	{
		if (isset($vmid) && isset($node)) {
			if (is_null($snapname)) {
				$parameters = array(
					"vmid" => $vmid,
					"node" => $node,
					"vmstate" => true,
				);
			} else {
				$parameters = array(
					"vmid" => $vmid,
					"node" => $node,
					"vmstate" => true,
					"snapname" => $snapname,
				);
			}
			$url = "/nodes/" . $node . "/qemu/" . $vmid . "/snapshot";
			$post = $this->post($url, $parameters);
			if ($post) {
				error_log("Created snapshot for vm " . $vmid . "");
				return true;
			} else {
				error_log("Error creating snapshot for vm " . $vmid . "");
				return false;
			}
		} else {
			error_log("no vm or node valid");
			return false;
		}
	}

	/*
	 * bool|string get_version ()
	 * Return the version and minor revision of Proxmox Server
	 */
	public function get_version()
	{
		$version = $this->get("/version");
		if ($version == null) {
			return false;
		} else {
			return $version['version'];
		}
	}

	/*
	 * object/array? get (string action_path)
	 */
	public function get($action_path)
	{
		return $this->action($action_path, "GET");
	}

	/*
	 * bool put (string action_path, array parameters)
	 */
	public function put($action_path, $parameters)
	{
		return $this->action($action_path, "PUT", $parameters);
	}

	/*
	 * bool post (string action_path, array parameters)
	 */
	public function post($action_path, $parameters)
	{
		return $this->action($action_path, "POST", $parameters);
	}

	/*
	 * bool delete (string action_path)
	 */
	public function delete($action_path)
	{
		return $this->action($action_path, "DELETE");
	}
}

?>
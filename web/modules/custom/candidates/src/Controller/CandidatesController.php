<?php
/**
* @file
* Contains \Drupal\candidates\Controller\CandidatesController.
*/
namespace Drupal\candidates\Controller;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\user\Entity\User;
use Drupal\user\Entity\Role;
use Drupal\node\Entity\Node;

//Controller routines for candidates routes

class CandidatesController extends ControllerBase {
	/**
	* Get All Candidates Details API
	*/
	public function get_candidate_detail(Request $request) {
		global $base_url;
		try{
			$content = $request->getContent();
			$params = json_decode($content, TRUE);
			$uid = $params['uid'];
			$user = User::load($uid);
			$all_candidates = array();
			$candidate_fetch = \Drupal::database()->query("SELECT node_field_data.created AS node_field_data_created, node_field_data.nid AS nid
			FROM {node_field_data} node_field_data
			WHERE (node_field_data.status = '1') AND (node_field_data.type IN ('candidate'))
			ORDER BY node_field_data_created ASC");
			$candidate_fetch_details = $candidate_fetch->fetchAll();

			foreach($candidate_fetch_details as $key => $node_id){
				$nodeid = $node_id->nid;
				$node = \Drupal::EntityTypeManager()->getStorage('node')->load($nodeid);
				$candidate_details['id'] = $nodeid;
				$candidate_details['name'] = $node->get('title')->value;
				$candidate_details['email'] = $node->get('field_email_id')->value;
				$date = date_create($node->get('field_date_of_birth')->value);
				$date_of_birth = date_format($date, "d M Y");
				$candidate_details['dob'] = $date_of_birth;
				$candidate_details['gender'] = ucfirst($node->get('field_gender')->value);
				$candidate_details['country'] = $node->get('field_country')->value;
				array_push($all_candidates, $candidate_details);
			}
			$final_api_reponse = array(
				"status" => "OK",
				"message" => "All Candidate List",
				"result" => $all_candidates
			);
			return new JsonResponse($final_api_reponse);
		}
		catch(Exception $exception) {
			$this->exception_error_msg($exception->getMessage());
		}
	}

	/**
	* Add Candidates Details API
	*/
	public function add_candidate_detail(Request $request){
		global $base_url;
		try{
			$content = $request->getContent();
			$params = json_decode($content, TRUE);

			$uid = $params['uid'];
			$user = User::load($uid);

			$date = explode('/', $params['candidate_dob']);
			$date_of_birth = $date[2] . "-" . $date[1] . "-" . $date[0];
			$newCandidate = Node::create([
				'type' => 'candidate',
				'uid' => 1,
				'title' => array('value' => $params['candidate_name']),
				'field_email_id' => array('value' => $params['candidate_email']),
				'field_date_of_birth' => array('value' => $date_of_birth),
				'field_gender' => array('value' => $params['candidate_gender']),
				'field_country' => array('value' => $params['candidate_country']),
			]);

			// Makes sure this creates a new node
			$newCandidate->enforceIsNew();
			// Saves the node
			// Can also be used without enforceIsNew() which will update the node if a $newCandidate->id() already exists
			$newCandidate->save();
			$nid = $newCandidate->id();
			$new_candidate_details = $this->fetch_candidate_detail($nid);
			$final_api_reponse = array(
				"status" => "OK",
				"message" => "Candidate Data Added Successfully",
				"result" => $new_candidate_details,
			);
			return new JsonResponse($final_api_reponse);
		}
		catch(Exception $exception) {
			$this->exception_error_msg($exception->getMessage());
		}
	}

	/**
	* Edit Candidates Details API
	*/
	public function edit_candidate_detail(Request $request){
		global $base_url;
		try{
			$content = $request->getContent();/* reads json input from login API callback */
			$params = json_decode($content, TRUE);

			$uid = $params['uid'];
			$user = User::load($uid);

			$nid = $params['nid'];
			$date = explode('/', $params['candidate_dob']);
			$date_of_birth = $date[2] . "-" . $date[1] . "-" . $date[0];

			if(!empty($nid)){
				$node = \Drupal::EntityTypeManager()->getStorage('node')->load($nid);
				$node->set("field_email_id", array('value' => $params['candidate_email']));
				$node->set("field_date_of_birth", array('value' => $date_of_birth));
				$node->set("field_gender", array('value' => $params['candidate_gender']));
				$node->set("field_country", array('value' => $params['candidate_country']));
				$node->save();

				$updated_candidate_details = $this->fetch_candidate_detail($nid);
				$final_api_reponse = array(
					"status" => "OK",
					"message" => "Candidate Data Updated Successfully",
					"result" => $updated_candidate_details,
				);
			}
			else{
				$this->exception_error_msg('Candidate ID is reqired');
			}
			return new JsonResponse($final_api_reponse);
		}
		catch(Exception $exception) {
			$this->exception_error_msg($exception->getMessage());
		}
	}

	/**
	* Delete Candidates Details API
	*/
	public function delete_candidate(Request $request){
		global $base_url;
		try{
			$content = $request->getContent();
			$params = json_decode($content, TRUE);
			$nid = $params['nid'];
			if(!empty($nid)){
				$deleted_candidate_details = $this->fetch_candidate_detail($nid);
				$node = \Drupal::EntityTypeManager()->getStorage('node')->load($nid);	
				$node->delete();
				$final_api_reponse = array(
					"status" => "OK",
					"message" => "Candidate record has been deleted successfully",
					"result" => $deleted_candidate_details,
				);
			}
			return new JsonResponse($final_api_reponse);
		}
		catch(Exception $exception) {
			$web_service->error_exception_msg($exception->getMessage());
		}
	}

	/**
	* Fetch Candidates Details API based on Node-ID
	*/
	public function fetch_candidate_detail($nid){
		if(!empty($nid)){
			$node = \Drupal::EntityTypeManager()->getStorage('node')->load($nid);

			$date = date_create($node->get('field_date_of_birth')->value);
			$date_of_birth = date_format($date, "d M Y");
			$candidate_details['name'] = $node->get('title')->value;
			$candidate_details['email'] = $node->get('field_email_id')->value;
			$candidate_details['dob'] = $date_of_birth;
			$candidate_details['gender'] = ucfirst($node->get('field_gender')->value);
			$candidate_details['country'] = $node->get('field_country')->value;

			$final_api_reponse = array(
				'candidate_detail' => $candidate_details
			);
			return $final_api_reponse;
		}
		else{
			$this->exception_error_msg("Candidate details not found.");
		}
	}

	public function all_candidates() {
		$candidates = array();
		$candidate_fetch = \Drupal::database()->query("SELECT node_field_data.created AS node_field_data_created, node_field_data.nid AS nid
		FROM {node_field_data} node_field_data
		WHERE (node_field_data.status = '1') AND (node_field_data.type IN ('candidate'))
		ORDER BY node_field_data_created ASC");
		$candidate_fetch_details = $candidate_fetch->fetchAll();

		foreach($candidate_fetch_details as $key => $node_id){
			$nodeid = $node_id->nid;
			$node = \Drupal::EntityTypeManager()->getStorage('node')->load($nodeid);
			$candidate_details['id'] = $nodeid;
			$candidate_details['name'] = $node->get('title')->value;
			$candidate_details['email'] = $node->get('field_email_id')->value;
			$date = date_create($node->get('field_date_of_birth')->value);
			$date_of_birth = date_format($date, "d M Y");
			$candidate_details['dob'] = $date_of_birth;
			$candidate_details['gender'] = ucfirst($node->get('field_gender')->value);
			$candidate_details['country'] = $node->get('field_country')->value;
			$candidate_details['passport'] = $node->get('field_passport')->value;
			$candidate_details['passport_number'] = $node->get('field_passport_number')->value;
			array_push($candidates, $candidate_details);
		}
		return [
			'#theme' => 'candidate_list',
			'#candidates' => $candidates,
			'#title' => 'All Candidates',
		];
	}
}
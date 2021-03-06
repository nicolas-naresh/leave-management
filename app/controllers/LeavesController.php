<?php
/**
 *
 * Class Name					:	LeavesController
 * author 						:	Jack Braj
 * Date							:	June 02, 2014
 * Purpose						:	Resourceful controller for leaves
 * Table referred				:	leaves, users, approvals
 * Table updated				:	leaves, approvals
 * Most Important Related Files	:  	/app/models/Leave.php
*/



class LeavesController extends \BaseController {

	public function __construct()
	{
	  $this->beforeFilter('auth');
	}


	/**
	 * Function Name	:	index
	 * Author Name		:	Jack Braj
	 * Date				:	June 02, 2014
	 * Parameters		:	none
	 * Purpose			:	This function used to list all leaves
	*/


	public function index()
	{
		$leaves = Leave::all();

		$extraLeaves = Extraleave::all();

		return View::make('leaves.index')->with("leaves", $leaves)->with('extraLeaves', $extraLeaves);
	}

	/**
	* Function Name		: 		create
	* Author Name		:		Jack Braj
	* Date				:		June 02, 2014
	* Parameters		:	   	none
	* Purpose			:		This function renders leave form with user data
	*/

	public function create()
	{
		$users = User::where('id', '<>', Auth::user()->id)->employee()->lists('name', 'id');
		ksort($users);
		$leave = new Leave();
		$leave->leave_type = "";
		$layout = Auth::user()->employeeType == "ADMIN" ? "admin_layout" : "user_layout";
		return View::make('leaves.create')->with('users', $users)->with('leave' , $leave)->with("layout", $layout);
	}

	/**
	* Function Name		: 		store
	* Author Name		:		Jack Braj
	* Date				:		June 02, 2014
	* Parameters		:	   	none
	* Purpose			:		This function used to create leave
	*/

	public function store()
	{
		$validator = [];
		$validator_leave = [];
		$validator_csr = [];
		$inputs = Input::all();
		if(isset($inputs['leave']['leave_date'])){
	  	$ldts = explode(",", $inputs['leave']['leave_date']);
	  	$temp_ldts = array();
	  	foreach($ldts as $ldt){
	  		$ldt = date("Y-m-d",strtotime($ldt));
	  		$temp_ldts[] = $ldt;
	  	}
	  	$inputs['leave']['leave_date'] = implode(",",$temp_ldts);
	  }

		$leave = $inputs['leave'];
		$leave['leave_option'] = $inputs['leave_option'] ;
		if($inputs['leave_option'] == 'CSR'){
			$leave['leave_type'] = 'CSR';
			$inputs['leave']['leave_option'] = 'CSR';
		}
		else{
			$inputs['leave']['leave_option'] = '';
		}

		$hasLeaveError = false;
		$hasApprovalError = false;
		$hasCsrError = false;


		$validator_leave = Validator::make($leave, Leave::$rules);
		$validator_leave->sometimes('leave_date', 'regex:/[\d]{4}-[\d]{2}-[\d]{2}([,][\d]{4}-[\d]{2}-[\d]{2})+/', function($input){
			return $input->leave_type === "MULTI";
		});

		$validator_leave->sometimes('leave_date', 'regex:/[\d]{4}-[\d]{2}-[\d]{2}([,][\d]{4}-[\d]{2}-[\d]{2})/', function($input){
			return $input->leave_type === "LONG";
		});

		$validator_leave->sometimes('leave_date', 'regex:/[\d]{4}-[\d]{2}-[\d]{2}/', function($input){
			$ltypes = array('FH','SH','LEAVE','CSR');
			return in_array($input->leave_type, $ltypes);
		});

		if($validator_leave->fails())
			$hasLeaveError = true;


		if( 'CSR' == $inputs['leave']['leave_option'] )
		{
			$csrs = $inputs['csr'];
			foreach($csrs as $key => $csr)
			{
				// $csr_slots[$key]['from_time'] = sprintf("%02s", $csr['from']['hour']).':' . sprintf("%02s", $csr['from']['min']);
				// $csr_slots[$key]['to_time'] = sprintf("%02s", $csr['to']['hour']) . ':' . sprintf("%02s", $csr['to']['min']);
				$validator_csr[$key] = Validator::make($csr, Csr::$rules);

				if($validator_csr[$key]->fails())
					$hasCsrError = true;
			}
		}

		// check if user has selected any approver or not
		if(!array_key_exists('approval', $inputs))
			$hasApprovalError = true;
		$hasEmployeeError = false;
		if(Input::get("employee_id") == -1){
			$hasEmployeeError = true;
		}
		if($hasLeaveError || $hasApprovalError || $hasCsrError || $hasEmployeeError)
		{
			$validator = ($hasLeaveError)? $validator_leave->messages()->toArray() : [];
			$validator = array_merge($validator, ($hasApprovalError)? ['approval' => ['Please select at least one approval']] : [] );

			$validator = array_merge($validator, ($hasEmployeeError) ? ['employee_id' => ['Please select an Employee']] : []);
			foreach($validator_csr as $vc)
			{
			 	$validator = array_merge($validator, ($hasCsrError)? $vc->messages()->toArray() : [] );
			}

			return Redirect::back()->withErrors($validator)->withInput($inputs)->with("error", "This form contains errors, please review and try again");
		}

		$tempLeave = $leave;
		$addedLeaves = [];

		// grab all leave dates in an array

		$leave_dates = explode(",", $leave['leave_date']);
		foreach($leave_dates as $key => $ldate){
			$leave_dates[$key] = date("Y-m-d",strtotime($ldate));
		}

		//checking if user or admin is adding new leave
		$user_id = "";
		if(Auth::user()->employeeType == "ADMIN"){
			$user = User::find(Input::get("employee_id"));
			$user_id = $user->id;
		}
		else{
			$user_id = Auth::user()->id;
		}

		if($tempLeave["leave_type"] == "MULTI"){
			foreach($leave_dates as $leave_date){
				$leave = $tempLeave;
				$leave["leave_date"] = $leave_date;
				$leave["leave_type"] = "LEAVE";
				$leave = array_merge($leave, ['user_id' => $user_id]);
				$leave = Leave::create($leave);

				$addedLeaves[] = $leave;
			}
		}

		else{
			if($tempLeave["leave_type"] == "LONG"){
				$leave = $tempLeave;
				$leave["leave_date"] = $leave_dates[0];
				$leave["leave_to"] = $leave_dates[1];
				$leave = array_merge($leave, ['user_id' => $user_id]);
				$leave = Leave::create($leave);
				$addedLeaves[] = $leave;
			}
			else{
				$leave = $tempLeave;
				$leave["leave_date"] = $leave_dates[0];
				$leave = array_merge($leave, ['user_id' => $user_id]);
				$leave = Leave::create($leave);
				$addedLeaves[] = $leave;
			}
		}

		if( 'CSR' == $inputs['leave']['leave_option'] )
		{
			foreach($csrs as $key => $slot)
			{
				$slot['leave_id'] = $addedLeaves[0]->id;
				$slot['from_time'] = date('H:i', strtotime($slot['from_time']));
				$slot['to_time'] = date('H:i', strtotime($slot['to_time']));
				Csr::create($slot);
			}
		}


		$approvals = $inputs['approval'];
		foreach($addedLeaves as $addedLeave){
			foreach($approvals as $approval)
			{
				$approval['leave_id'] = $addedLeave->id;
				$approval['approved'] = 'PENDING';
				$approval = Approval::create($approval);

				if(Auth::user()->employeeType == "ADMIN"){
					$approval->approved = 'YES';
					$approval->save();
					$approval->sendApprovalNotification();
    				$approval->markCalendarEvent();
				}
			}
		}
		if(Auth::user()->employeeType == "ADMIN"){
			return Redirect::to(URL::route('leaves.create'))
		->with('success', 'Leave successfully Added');
		}
		else{
			$approvals = Input::get("approval");
			$approval_ids = array();
			foreach($approvals as $appr){
				$approval_ids[] = (int)$appr["approver_id"];
			}
			return Redirect::to(URL::route('myLeaves'))->with('success', 'Leave successfully applied')->with("notification", array("noti_name" => "leave_added", "noti_getter" => $approval_ids));
		}
	}


	/**
	* Function Name		: 		show
	* Author Name		:		Jack Braj
	* Date				:		June 02, 2014
	* Parameters		:	    id
	* Purpose			:		This function used to show individual leave
	*/

	public function show($id)
	{
	  $leave = Leave::findOrFail($id);
	  return View::make('leaves.show', compact('leave'));
	}

	/**
	* Function Name		: 		edit
	* Author Name		:		Jack Braj
	* Date				:		June 02, 2014
	* Parameters		:	   	id
	* Purpose			:		This function used to render edit form with user information filled in
	*/

	public function edit($id)
	{
	  $layout = "user_layout";
	  $leave = Leave::find($id);
	  if( 'LONG' == $leave->leave_type ) {
	  	$leave->leave_date = date("Y-m-d",strtotime($leave->leave_date));
	  	$leave->leave_to = date("Y-m-d",strtotime($leave->leave_to));
	    $leave->leave_date .= ','. $leave->leave_to;
	  }
	  else{
	  	$leave->leave_date = date("Y-m-d",strtotime($leave->leave_date));
	  }
	  $users = User::where('id', '<>', Auth::user()->id)->employee()->lists('name', 'id');
	  $inputCSRs = array();

	  if($leave->leave_type == "CSR"){
	    $csrs = $leave->csrs;
	    foreach($csrs as $csr){
	      $from_time = preg_replace("/:00$/",'',$csr->from_time);
	      $to_time = preg_replace("/:00$/",'',$csr->to_time);
	      $inputCSRs[] = array("from_time" => $from_time, "to_time" => $to_time);
	    }
	  }
	  return View::make('leaves.edit', array('leave' => $leave, 'users' => $users, 'inputCSRs' => $inputCSRs, 'layout' => $layout));
	}

	/**
	* Function Name		: 		update
	* Author Name		:		Jack Braj
	* Date				:		June 02, 2014
	* Parameters		:	    id
	* Purpose			:		This function used to update individual leave
	*/

	public function update($id)
	{
	  $validator = [];
	  $validator_leave = [];
	  $validator_csr = [];
	  $inputs = Input::all();
	  if(isset($inputs['leave']['leave_date'])){
	  	$ldts = explode(",", $inputs['leave']['leave_date']);
	  	$temp_ldts = array();
	  	foreach($ldts as $ldt){
	  		$ldt = date("Y-m-d",strtotime($ldt));
	  		$temp_ldts[] = $ldt;
	  	}
	  	$inputs['leave']['leave_date'] = implode(",",$temp_ldts);
	  }
	  $leave = $inputs['leave'];
	  $hasLeaveError = false;
	  $hasApprovalError = false;
	  $hasCsrError = false;
	  $leaveObj = Leave::findOrFail($id);
	  $leave['leave_option'] = ($leaveObj->leave_type === 'CSR') ? 'CSR' : 'LEAVE';
	  $validator_leave = Validator::make($leave, Leave::$rules);

	  $validator_leave->sometimes('leave_date', 'regex:/[\d]{4}-[\d]{2}-[\d]{2}([,][\d]{4}-[\d]{2}-[\d]{2})+/', function($input){
	    return $input->leave_type === "MULTI";
	  });

	  $validator_leave->sometimes('leave_date', 'regex:/[\d]{4}-[\d]{2}-[\d]{2}([,][\d]{4}-[\d]{2}-[\d]{2})/', function($input){
	    return $input->leave_type === "LONG";
	  });

	  $validator_leave->sometimes('leave_date', 'regex:/[\d]{4}-[\d]{2}-[\d]{2}/', function($input){
	    $ltypes = array('FH','SH','LEAVE','CSR');
	    return in_array($input->leave_type, $ltypes);
	  });


	  if($validator_leave->fails())
	    $hasLeaveError = true;

	  if( 'CSR' == $leave['leave_option'] )
	  {
	    $csrs = $inputs['csr'];

	    foreach($csrs as $key => $csr)
	    {
	      $validator_csr[$key] = Validator::make($csr, Csr::$rules);
	      if($validator_csr[$key]->fails())
					$hasCsrError = true;
		    }
	  }

  // check if user has selected any approver or not

	  if(!array_key_exists('approval', $inputs))
	    $hasApprovalError = true;


	  if($hasLeaveError || $hasApprovalError || $hasCsrError)
	  {
	    $validator = ($hasLeaveError)? $validator_leave->messages()->toArray() : [];
	    $validator = array_merge($validator, ($hasApprovalError)? ['approval' => ['Please select at least one approval']] : [] );
	    foreach($validator_csr as $vc)
	    {
	      $validator = array_merge($validator, ($hasCsrError)? $vc->messages()->toArray() : [] );
	    }

	    return Redirect::back()->withErrors($validator)->withInput($inputs)->with("error", "This form contains errors, please review and try again");
	  }

	  $leave = array_merge($leave, ['user_id' => Auth::user()->id]);

	  if($leaveObj->leave_type === "LONG" || $leave["leave_type"] === "LONG"){
	    $ldates = explode(",",$leave["leave_date"]);
	    $leave_date = $ldates[0];
	    $leave_to = $ldates[1];
	    $leave["leave_date"] = date("Y-m-d",strtotime($leave_date));
	    $leave["leave_to"] = date("Y-m-d",strtotime($leave_to));
	  }
	  else{
	  	$leave["leave_date"] = $leave["leave_date"];
	  }


	  $leaveObj->update($leave);

	  if( 'CSR' == $inputs['leave_option'] )
	  {
	    $leaveObj->csrs()->forceDelete();
	    foreach($csrs as $slot)
	    {
			$slot['leave_id'] = $leaveObj->id;
			$slot['from_time'] = date("H:i",strtotime($slot['from_time']));
			$slot['to_time'] = date("H:i",strtotime($slot['to_time']));
			Csr::create($slot);
	    }
	  }
	  $approvals = $inputs['approval'];

	  $leaveObj->approvals()->forceDelete();
	  foreach($approvals as $approval)
	  {
	    $approval['leave_id'] = $leaveObj->id;
	    $approval['approved'] = 'PENDING';
	    Approval::create($approval);
	  }
  	return Redirect::to(URL::route('myLeaves'))
		->with('success', 'Leave successfully applied');
	}

	/**
	* Function Name		: 		destroy
	* Author Name		:		Jack Braj
	* Date				:		June 02, 2014
	* Parameters		:	    id
	* Purpose			:		This function used to delete a single leave
	*/

	public function destroy($id)
	{
	  $leave = Leave::find($id);
	  $leave->approvals()->forceDelete();
	  if($leave->leave_type == "CSR"){
	    $leave->csrs()->forceDelete();
	  }
	  Leave::destroy($id);
	  return Response::json(array('status' => true));
	}


	/**
	* Function Name		: 		myLeaves
	* Author Name		:		Jack Braj
	* Date				:		June 03, 2014
	* Parameters		:	   	none
	* Purpose			:		This function is used for listing all leaves/csrs
	*/

	public function myLeaves(){
	  $leaves = Leave::where('user_id', '=', Auth::user()->id)->get();
	  return View::make('leaves.myleaves')->with('leaves', $leaves);
	}


	/**
	* Function Name		: 		leaveRequests
	* Author Name		:		Jack Braj
	* Date				:		June 03, 2014
	* Parameters		:	    none
	* Purpose			:		This function is used for listing all leaves/csrs
	*/

	public function leaveRequests(){
	  $leaveRequests = Approval::where("approver_id",Auth::user()->id)->get();
	  return View::make('leaves.leaverequests')->with("leaveRequests",$leaveRequests);
	}



	public function getReport(){
	  $searchData = Input::all();
	  $leaves = null;
	  $users = User::employee()->lists('name', 'id');
	  $users[0] = "All Employees";
	  ksort($users);

	  if(!empty($searchData)){

	  	if($searchData["leave_type"] == "ALL"){
  			if(isset($searchData["employee_id"])){
  				if($searchData["employee_id"] == 0){
  					$leaves = Leave::all();
  					$extraLeaves = Extraleave::where("for_year",date("Y"))->get();
  				}
  				else{
  					$leaves = Leave::where("user_id", $searchData["employee_id"])->get();
  					$extraLeaves = Extraleave::where("for_year",date("Y"))->where("user_id",$searchData["employee_id"])->get();
  				}
  			}
  			else{
  				$leaves = Leave::all();
  				$extraLeaves = Extraleave::where("for_year",date("Y"))->get();
  			}

	  		foreach($extraLeaves as $el){
	  			$le = new Leave();
	  			$le->leave_date = $el->from_date;
	  			$le->leave_to = $el->to_date;
	  			$le->reason = $el->description;
	  			$le->user_id = $el->user_id;
	  			$le->leave_type = $el->description;
	  			$leaves = $leaves->merge(array($le));
	  		}
	  	}
	  	else{
	  		$user = User::find($searchData["employee_id"]);
		    	$leaveType = $searchData["leave_type"];
		    	if(isset($searchData["employee_id"]) ){
		    		if($searchData["employee_id"] == 0){
		    			$leaves = new Leave();
		    		}
		    		else{
		    			$leaves = Leave::where("user_id", $user->id);
		    		}

		    	}
			switch($searchData['date_option']){
				case "between-dates":
				$leaves = $leaves->whereBetween("leave_date", array($searchData["from_date"], $searchData["to_date"]))
				->where("leave_type", $leaveType)
				->get();
				break;
				case "by-date":
				$leaves = $leaves->where("leave_date", $searchData["on_date"])
				->where("leave_type", $leaveType)
				->get();
				break;
				case "by-year":
					if(Config::get("database.default") == "mysql"){
						$leaves = $leaves->where(DB::raw('YEAR(leave_date)'), $searchData["year"])
						->where("leave_type", $leaveType)
						->get();
					}
					else{
						$leaves = $leaves->where(DB::raw('EXTRACT(YEAR FROM "leave_date"::date)'), $searchData["year"])
						->where("leave_type", $leaveType)
						->get();
					}
					break;
				case "by-month":
					if(Config::get("database.default") == "mysql"){
						$leaves = $leaves->where(DB::raw('YEAR(leave_date)'), date("Y"))
						->where(DB::raw('MONTH(leave_date)'), date("m"))
						->where("leave_type", $leaveType)
						->get();
					}
					else{
						$leaves = $leaves->where(DB::raw('EXTRACT(YEAR FROM "leave_date"::date)'), date("Y"))
						->where(DB::raw('EXTRACT(MONTH FROM "leave_date"::date)'), date("m"))
						->where("leave_type", $leaveType)
						->get();
					}
					break;
			}
	  	}

	  }
	  return View::make('leaves.report')->withInput($searchData)->with("leaves",$leaves)->with("users", $users);
	}

	public function pendingLeaves(){
		$leaves = Leave::pendingLeaves();
		return View::make('leaves.index')->with("leaves", $leaves)->with("extraLeaves", array());
	}

	public function generalReport(){
		$all_users = User::where("employeeType","!=","ADMIN")->get();
		$extra_leave_names = array(
			"Marriage",
			"Paternity",
			"Maternity",
			"Education",
			"Other"
		);
		return View::Make("leaves.general_report", array(
				"all_users" => $all_users,
				"extra_leave_names" => $extra_leave_names
			)
		);
	}

	public function fetchGeneralReport(){
		$month = Input::get("month");
		$year = Input::get("year");
		$firstDay = 1;
		$firstDate = new DateTime( date("Y") . "-" . sprintf("%02s",$month) . '-01' );
		$lastDay = (int)$firstDate->format("t");
		$allUsers = User::where("employeeType","!=","ADMIN")->get();
		return View::make("leaves.fetch_next_general_report", array("lastDay" => $lastDay, "allUsers" => $allUsers, "month" => $month, "year" => $year));
	}

	public function getAddLeave(){
		$leave = new Leave();
		$users = User::where('id', '<>', Auth::user()->id)->employee()->lists('name', 'id');
		$layout = Auth::user()->employeeType == "ADMIN" ? "admin_layout" : "user_layout";
		return View::make("leaves.newleaves.add_leave", array("leave" => $leave, "layout" => $layout, "users" => $users));
	}

	public function postAddLeave(){
		$allInputs = Input::all();
		$fromDate = $allInputs["from_date"];
		$toDate = $allInputs["to_date"];
		$leaveType = $allInputs["leaveType"];
		$userOfficeInTime = $allInputs["userOfficeInTime"];
		$userOfficeOutTime = $allInputs["userOfficeOutTime"];
		$userLeaveInTime = $allInputs["userLeaveInTime"];
		$userLeaveOutTime = $allInputs["userLeaveOutTime"];
		$leaveReason = $allInputs["leave_reason"];
		$user = Auth::user("id");
		$leave = new Leave();
		$leave->leave_type = $leaveType;

		$leave->leave_date = date("Y-m-d",strtotime($fromDate));
		$leave->reason = $leaveReason;
		$leave->user_id = $user->id;
		switch($leaveType){
			case "LONG":
				$leave->leave_to = date("Y-m-d",strtotime($toDate));
				break;
			case "FH":
			case "SH":
				$leave->in_time = date('H:i:s', strtotime($userOfficeInTime));
				$leave->out_time = date('H:i:s', strtotime($userOfficeOutTime));
				$leave->available_in_time = date('H:i:s', strtotime($available_in_time));
				$leave->available_out_time = date('H:i:s', strtotime($available_out_time));
				break;
		}
		$leave->save();
		foreach($allInputs["approvals"] as $approver_id){
			$approval = new Approval();
			$approval->approver_id = $approver_id;
			$approval->leave_id = $leave->id;
			$approval->approval_note = "";
			$approval->approved = "PENDING";
			$approval->save();
			unset($approval);
		}
		return array(
			"status" => true
		);
	}

	public function getEditLeave(){
		return View::make("leaves.newleaves.edit_leave");
	}

	public function postEditLeave(){

	}

	public function getAddCSR(){
		return View::make("leaves.newleaves.add_csr");
	}

	public function postAddCSR(){

	}

	public function getEditCSR(){
		return View::make("leaves.newleaves.edit_csr");
	}

	public function postEditCSR(){

	}

}

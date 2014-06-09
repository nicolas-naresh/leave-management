@extends('layouts.user_layout')

@section('content')
  <div class="row">
    <div class="col-lg-12">
      <table class="table table-striped table-hover table-condensed" id="leavesTable">
	<thead>
	  <tr>
	    <th>
	      Name
	    </th>
	    <th>
	      Leave Date
	    </th>
	    <th>
	      Leave Type
	    </th>
	    <th class="text-center">
	      From Time
	    </th>
	    <th class="text-center">
	      To Time
	    </th>
	    <th>
	      Reason
	    </th>
	    <th class="text-center">
	      Approve
	    </th>
	  </tr>
	</thead>
	<tbody>
	  @foreach ($leaveRequests as $leaveReq)
	    <tr>
	      <td>
		{{$leaveReq->leave->user->name}}
	      </td>
	      <td>
		{{$leaveReq->leave->leave_date}}
	      </td>
	      <td>
		{{$leaveReq->leave->leave_type}}
	      </td>
	      <td align="center">
		@if ($leaveReq->leave->leave_type === "LEAVE")
		  --
		@else
		  {{$leaveReq->leave->from_time}}
		@endif
		
	      </td>
	      <td align="center">
		@if ($leaveReq->leave->leave_type === "LEAVE")
		  --
		@else
		  {{$leaveReq->leave->to_time}}
		@endif
	      </td>
	      <td>
		{{$leaveReq->leave->reason}}
	      </td>
	      <td align="center">
                @if ($leaveReq->approved == "PENDING")
                  <a title="Approve Leave" class="btn btn-xs btn-primary approve-status-change" data-approve_status="YES" data-approval_id = "{{ $leaveReq->id }}" data-approval_url="{{ URL::route('approval.updateStatus') }}">
		    <span class="glyphicon glyphicon-ok"></span>
		  </a>
		  &nbsp;&nbsp;
		  <a title="Reject Leave" class="btn btn-xs btn-primary approve-status-change" data-approve_status="NO" data-approval_id = "{{ $leaveReq->id }}" data-approval_url="{{ URL::route('approval.updateStatus') }}">
		    <span class="glyphicon glyphicon-remove"></span>
		  </a>
                @else
                  @if ($leaveReq->approved == "YES")
                    <a href="javascript: void(0);" class="btn btn-xs btn-primary approve-status-change btn-success">
		      <span class="glyphicon glyphicon-ok" title="Leave Approved"></span>
		    </a>
                  @else
                    <a href="javascript: void(0);" class="btn btn-xs btn-primary approve-status-change btn-danger">
		      <span class="glyphicon glyphicon-remove btn-danger" title="Leave Rejected"></span>
		    </a>
                  @endif
                @endif
	      </td>
	    </tr>
	  @endforeach
	</tbody>
      </table>
    </div>
  </div>
@stop
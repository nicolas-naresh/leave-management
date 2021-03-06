<thead>
  <tr>
  <th>
      User
    </th>
    <th style="vertical-align: middle; text-align: center;">
      Date
    </th>
    @if($leaves->first()->leave_type === "CSR")
      <th style="width: 21.5%;">
        From Time
      </th>
      <th>
        To Time
      </th>
    @else
      <th>
        Reason
      </th>
    @endif
    <th style="text-align:center">
      Approved By
    </th>
  </tr>
</thead>

  @foreach($leaves as $leave)
    <tbody>
      <tr>
       <td>
        {{ $leave->user->name }}
      </td>
        @if($leave->leave_type === "LONG")
          <td class="text-center">
          {{ date("d-m-Y",strtotime($leave->leave_date)) }} - {{ date("d-m-Y",strtotime($leave->leave_to)) }}</td>
        @else
          <td  class="text-center">
          {{ date("d-m-Y",strtotime($leave->leave_date)) }}</td>
        @endif
        <td>{{ $leave->reason }}</td>
        <td align="center">
          <a data-toggle="tooltip" title="View Approvals" class="btn btn-primary normal-button btn-xs view-approvals" data-url="{{ URL::route('approval.leaveApprovals', array('id' => $leave->id))}}" title="View Approvals"><span class="glyphicon glyphicon-eye-open"></span></a>
          <a  data-toggle="tooltip" title="Delete"   class="btn btn-danger normal-button btn-xs delete-myleave" data-url="{{ URL::Route('leaves.destroy', array($leave->id)) }}"><span class="glyphicon glyphicon-remove" title="Delete Leave"></span></a>
        </td>
        @if($leave->leave_type === "CSR")
          <td colspan="2">
            <table class="table table-bordered margin-bottom-0">
              @foreach($leave->csrs as $csr)
                <tr>
                  <td>
                    {{ $csr->from_time }}
                  </td>
                  <td>
                    {{ $csr->to_time }}
                  </td>
                </tr>
              @endforeach
            </table>
          </td>
          <td align="center" style="vertical-align: middle;">
            <a  data-toggle="tooltip" title="View Approvals"  class="btn btn-primary normal-button btn-xs view-approvals" data-url="{{ URL::route('approval.leaveApprovals', array('id' => $leave->id))}}"><span class="glyphicon glyphicon-eye-open"></span></a>
            <a  data-toggle="tooltip" title="Delete"  class="btn btn-danger normal-button btn-xs delete-myleave" data-url="{{ URL::Route('leaves.destroy', array($leave->id)) }}"><span class="glyphicon glyphicon-remove"></span></a>
          </td>
        @endif
      </tr>
    </tbody>
  @endforeach
</table>
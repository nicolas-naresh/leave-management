<table class="table table-bordered table-stripped">
  <thead>
    <tr>
      <th>Approval From</th>
      <th class="text-center">Approval Status</th>
    </tr>
  </thead>
  <tbody>
    @foreach($leave->approvals as $approval)
      <tr>
        <td>{{$approval->approver->name}}</td>
        <td class="text-center">
          @if("PENDING" === $approval->approved)
            <span class="glyphicon glyphicon-question-sign info btn-info padding_4 round-icon"></span>
          @else
            @if("YES" === $approval->approved)
              <span class="glyphicon glyphicon-ok-circle success btn-success padding_4 round-icon"></span>
            @else
              <span class="glyphicon glyphicon-remove-circle danger btn-danger padding_4 round-icon"></span>
            @endif
          @endif
        </td>
      </tr>
    @endforeach
  </tbody>
</table>
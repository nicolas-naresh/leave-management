@extends('layouts.' . $layout)

@section('content')

<div class="row">
  <div class="col-sm-12">
    {{ Form::open(array('url' => URL::route('leaves.store'), 'method' => 'post', 'class' => 'form-horizontal', 'id' => 'leaves_create_form')) }}
      <legend class="form-signin-heading">Apply for Leave</legend>
      @include("leaves.form", array("leave" => $leave, "layout" => $layout))
      <div class="form-group">
        <div class="col-sm-12">
          <div class="row">
            <div class="col-sm-6 col-sm-offset-2">
              {{ Form::submit("Apply", array("class" => "btn btn-primary normal-button pull-right")) }}
            </div>
          </div>
        </div>
      </div>
    {{ Form::close() }}
  </div>
</div>
@stop
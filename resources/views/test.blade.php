<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
	</head>
    <body>
	<div>
		<div>
			<span>IDX</span>
			<div>
				<h4>http://rets.torontomls.net:6103/rets-treb3pv/server/login</h4>
				<h4>D19cgu</h4>
				<h4>7M$5y83</h4>
			</div>		
			<form id="form_1" name="form_1" method="post" action="/test?type=idx" enctype="multipart/form-data">
				{{ csrf_field() }}
				<button type="submit">Test</button>
			</form>
			
			
			@if(isset($resultIdx))
				<div style="background: #94858526;margin-top: 10px;padding: 10px;">
				{{$resultIdx}}
				</div>
			@endif
			
			
		</div>
		<hr>
		<div>
			<span>VOW</span>
			<div>
				<h4>http://retsau.torontomls.net:6103/rets-treb3pv/server/login</h4>
				<h4>EV19akr</h4>
				<h4>36$28Yz</h4>
			</div>		
			<form id="form_2" name="form_2" method="post" action="/test?type=vow" enctype="multipart/form-data">
				{{ csrf_field() }}
				<button type="submit">Test</button>
			</form>
			
			@if(isset($resultVow))
				<div style="background: #94858526;margin-top: 10px;padding: 10px;">
				{{$resultVow}}
				</div>
			@endif
		
		</div>
	</div>
    </body>
</html>

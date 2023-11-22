<?php
 $con = mysqli_connect('localhost','admin','Welcome#123','mysql_customer_orders');
?>
<!DOCTYPE html>
<html lang="en-US">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body {
  font-family: Arial;
  color: white;
}

.split {
  height: 100%;
  width: 50%;
  position: fixed;
  z-index: 1;
  top: 0;
  overflow-x: hidden;
  padding-top: 20px;
}

.left {
  left: 0;
  background-color: #111;
}

.right {
  right: 0;
  background-color: red;
}

.centered {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
}

.centered img {
  width: 150px;
  border-radius: 50%;
}
</style>
</head>
<body>

<h1>My Web Page</h1>
<div class="split left">
<div class="centered">
<div id="piechart"></div>

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

<script type="text/javascript">
// Load google charts
google.charts.load('current', {'packages':['corechart']});
google.charts.setOnLoadCallback(drawChart);

// Draw the chart and set the chart values
function drawChart() {
 var data = google.visualization.arrayToDataTable([

 ['Name','Total'],
 <?php
                   	$query = "select full_name, round(sum(unit_price*quantity)) as total  
                    from customer_order_products  where order_status ='COMPLETE' 
                    group by customer_id order by total desc limit 10;";

                         $exec = mysqli_query($con,$query);
                         while($row = mysqli_fetch_array($exec)){

                         echo "['".$row['full_name']."',".$row['total']."],";
                         }
                         ?> 
 
 ]);

  // Optional; add a title and set the width and height of the chart
  var options = {'title':'Top 10 spenders', 
                'width':650, 'height':400};

  // Display the chart inside the <div> element with id="piechart"
  var chart = new google.visualization.PieChart(document.getElementById('piechart'));
  chart.draw(data, options);
}
</script>
</div>
</div>
<div class="split right">
<div class="centered">
<?php

$link = mysqli_connect('localhost','admin','Welcome#123','mysql_customer_orders');
#require_once "config_2.php";
$query = "select   round(max(order_total),2) order_total,
dv.vendor_name delivery_vendor,deo.order_status delivery_status
from customer_order_products  cop
join delivery_orders deo on deo.order_id = cop.order_id
join DELIVERY_VENDOR dv on dv.id = deo.order_id
where deo.order_status not in ('COMPLETE')
group by dv.vendor_name,deo.order_status
limit 10;";
if ($stmt = $link->prepare($query)) {
   $stmt->execute();
   $stmt->bind_result($order_total,$delivery_vendor, $delivery_status);
echo "<h2>Orders not delivered</h2>";

   echo "<table>";
    echo "<tr>";
    echo "<th>order_total</th>";
    echo "<th>delivery_vendor</th>";
    echo "<th>delivery_status</th>";
echo "</tr>";

while ($stmt->fetch()) {
    echo "<tr>";
       echo "<td>" . $order_total ."</td>";
       echo "<td>" . $delivery_vendor ."</td>";
       echo "<td>" . $delivery_status ."</td>";
    echo "</tr>";
 }

$stmt->close();
}
?>
</div>
</div>
<!-- Code injected by live-server -->
<script>
	// <![CDATA[  <-- For SVG support
	if ('WebSocket' in window) {
		(function () {
			function refreshCSS() {
				var sheets = [].slice.call(document.getElementsByTagName("link"));
				var head = document.getElementsByTagName("head")[0];
				for (var i = 0; i < sheets.length; ++i) {
					var elem = sheets[i];
					var parent = elem.parentElement || head;
					parent.removeChild(elem);
					var rel = elem.rel;
					if (elem.href && typeof rel != "string" || rel.length == 0 || rel.toLowerCase() == "stylesheet") {
						var url = elem.href.replace(/(&|\?)_cacheOverride=\d+/, '');
						elem.href = url + (url.indexOf('?') >= 0 ? '&' : '?') + '_cacheOverride=' + (new Date().valueOf());
					}
					parent.appendChild(elem);
				}
			}
			var protocol = window.location.protocol === 'http:' ? 'ws://' : 'wss://';
			var address = protocol + window.location.host + window.location.pathname + '/ws';
			var socket = new WebSocket(address);
			socket.onmessage = function (msg) {
				if (msg.data == 'reload') window.location.reload();
				else if (msg.data == 'refreshcss') refreshCSS();
			};
			if (sessionStorage && !sessionStorage.getItem('IsThisFirstTime_Log_From_LiveServer')) {
				console.log('Live reload enabled.');
				sessionStorage.setItem('IsThisFirstTime_Log_From_LiveServer', true);
			}
		})();
	}
	else {
		console.error('Upgrade your browser. This Browser is NOT supported WebSocket for Live-Reloading.');
	}
	// ]]>
</script>
</body>
</html>

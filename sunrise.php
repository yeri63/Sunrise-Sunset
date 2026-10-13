<?php
    # Program: sunrise.php
    #    Date: 30-Oct-2025
    #  Author: gerry.walterscheid@gmail.com
    # Descrip: Display sunrise, zenith, sunset, daylight hrs, moon phase and illumination %.

    session_start();
    header("Content-type: text/xml");

    $delay = 300;
    header("Refresh:$delay");

    $urlPath = "http://192.168.3.250/cisco/sunrise.php";

    # Recall prior variable state
    $id = $_SESSION['id'] ?? "";                            # App name.

    # Prevent data corruption to next application.
    if ($id == "sunrise") {
        $dateTime = new DateTime($_SESSION['dateTime']);    # Last date.
        $mode = $_SESSION['mode'] + 0;                      # Current mode.
        $data = $_SESSION['data'];                          # JSON data.
        $update = 0;
        $lastUpdate = $_SESSION['lastUpdate']; }            # Time from prior page update.

    else {
        $dateTime = new DateTime(date('Y-m-d H:i'));
        $mode = 0; 
        $update = 1; 
        $lastUpdate = 0; }

    # Get SoftKey selection.
    $cmd = $_GET['key'] ?? 0;

    # Check page refresh delay.
    if (time() - $lastUpdate < $delay) 
        # Process user request based on the key pressed.	
        switch($cmd) {

        case 1 : # mode
            $mode = ++$mode % 2;
            $update = 0;
            break;

        case 2 : # previous
            $dateTime = $dateTime->modify('previous day');
            $update = 1;
            break;

        case 3 : # next
            $dateTime = $dateTime->modify('next day');
            $update = 1;
            break; }

    else {
        # Select the current date after last page refresh delay.
        $dateTime = new DateTime(date('Y-m-d H:i'));
        $mode = 0;
        $update = 1; }

    // Lookup twilight, sunrise/sunset and zenith from US Navy website.

    $date = $dateTime->format('Y-m-d');
    $day = $dateTime->format('d');

    # Coordinates for Orlando, FL.
    $lat = 28.54;
    $long = -81.38;

    $json_url="https://aa.usno.navy.mil/api/rstt/oneday?date=$date%20&coords=$lat,%20$long&tz=-5%20&dst=true";

    # Only update if the date has changed.
    if ($update == 1) {
        $json = file_get_contents($json_url);
        $data = json_decode($json, TRUE); }

    # Collect sun data.
    $begin_twlt = substr($data['properties']['data']['sundata'][0]['time'],0,5);
    $sunrise = substr($data['properties']['data']['sundata'][1]['time'],0,5);
    $zenith = substr($data['properties']['data']['sundata'][2]['time'],0,5);
    $sunset = substr($data['properties']['data']['sundata'][3]['time'],0,5);
    $end_twlt = substr($data['properties']['data']['sundata'][4]['time'],0,5);

    # Calculate hours of daylight.	
    $rise = new DateTime($sunrise);
    $set = new DateTime($sunset);
    $diff = $rise->diff($set);
    $daylight = $diff->format("%H:%I");
    $hrs = date('H', strtotime($daylight));
    $min = date('i', strtotime($daylight));

    # Collect moon phase.
    $phase = $data['properties']['data']['curphase'];
    $illum = $data['properties']['data']['fracillum'];

    # Collect moon data.
    $moonrise = substr($data['properties']['data']['moondata'][0]['time'],0,5);
    $moonzenith = substr($data['properties']['data']['moondata'][1]['time'],0,5);
    $moonset = substr($data['properties']['data']['moondata'][2]['time'],0,5);

    if ($data['properties']['data']['closestphase']['day'] == $day)
        $phase = $data['properties']['data']['closestphase']['phase'];

    $date = $dateTime->format('D, M jS, Y');

    switch($mode) {

    case 0 : # Sunrise/Sunset Times
        echo "<CiscoIPPhoneText>\n";
        echo "  <Title>Sunrise/Sunset Times</Title>\n";

        echo "  <Text>";
        echo "Sunrise: $sunrise  (Twilight: $begin_twlt)\n";
        echo "Zenith: $zenith     (Solar Noon)\n";
        echo "Sunset: $sunset   (Twilight: $end_twlt)\n";
        echo "Daylight: $hrs hrs $min min";
        break;

    case 1 : # Moon Phase
        echo "<CiscoIPPhoneText>\n";
        echo "  <Title>Sunrise/Sunset - Moon Phase</Title>\n";

        echo "  <Text>";
        echo "Moon Phase: $phase\n";
        echo "Illumination: $illum";
        break; }

    echo "</Text>\n";

    echo "  <SoftKeyItem>\n";
    echo "    <Name>Mode</Name>\n";
    echo "    <URL>$urlPath?key=1</URL>\n";
    echo "    <Position>1</Position>\n";
    echo "  </SoftKeyItem>\n";

    echo "  <SoftKeyItem>\n";
    echo "    <Name>Prev</Name>\n";
    echo "    <URL>$urlPath?key=2</URL>\n";
    echo "    <Position>2</Position>\n";
    echo "  </SoftKeyItem>\n";

    echo "  <SoftKeyItem>\n";
    echo "    <Name>Next</Name>\n";
    echo "    <URL>$urlPath?key=3</URL>\n";
    echo "    <Position>3</Position>\n";
    echo "  </SoftKeyItem>\n";

    echo "  <SoftKeyItem>\n";
    echo "    <Name>Exit</Name>\n";
    echo "    <URL>SoftKey:Exit</URL>\n";
    echo "    <Position>4</Position>\n";
    echo "  </SoftKeyItem>\n";

    echo "  <Prompt>Date:  $date</Prompt>\n";
    echo "</CiscoIPPhoneText>";

    # Save current variable state.
    $_SESSION['id'] = "sunrise";
    $_SESSION['cmd'] = $cmd;
    $_SESSION['dateTime'] = $dateTime->format('Y-m-d H:s');
    $_SESSION['mode'] = $mode;
    $_SESSION['data'] = $data;
    $_SESSION['lastUpdate'] = time();
?>

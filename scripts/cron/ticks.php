<?php

function cron()
{
  passthru('./controleonline.sh');
  echo "<br />\n";
  flush(); // keeps it flowing to the browser...
  sleep(1);
}

register_tick_function("cron");

declare(ticks=1) {
  while (true) {
  }   // to keep it running...
}

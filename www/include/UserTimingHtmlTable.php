<?php

class UserTimingHtmlTable {

  /* @var TestRunResults */
  private $runResults;
  private $hasNavTiming;
  private $hasUserTiming;
  private $userTimings;

  /**
   * UserTimingHtmlTable constructor.
   * @param TestRunResults $runResults Run results to use for the table
   */
  public function __construct($runResults) {
    $this->runResults = $runResults;
    $this->hasNavTiming = $runResults->hasValidMetric("loadEventStart") ||
                          $runResults->hasValidMetric("domContentLoadedEventStart");
    $this->hasUserTiming = $this->_initUserTimings();
  }

  public function create() {
    $data = $this->runResults->getStepResult(1)->getRawResults();
    $out = "";
    if ($this->hasUserTiming || $this->hasNavTiming)
    {
      $borderClass = '';
      if ($this->hasUserTiming)
        $borderClass = ' class="border"';
      $out .= '<table id="tableW3CTiming" class="pretty" align="center" border="1" cellpadding="10" cellspacing="0">';
      $out .= '<tr>';
      if ($this->hasUserTiming)
        foreach($this->userTimings[0] as $label => $value)
          $out .= '<th>' . htmlspecialchars($label) . '</th>';
      if ($this->hasNavTiming) {
        $out .= "<th$borderClass>";
        if ($data['firstPaint'] > 0)
          $out .= "RUM First Paint</th><th>";
        if (isset($data['domInteractive']) && $data['domInteractive'] > 0)
          $out .= "<a href=\"http://w3c.github.io/navigation-timing/#h-processing-model\">domInteractive</a></th><th>";
        $out .= "<a href=\"http://w3c.github.io/navigation-timing/#h-processing-model\">domContentLoaded</a></th><th><a href=\"http://w3c.github.io/navigation-timing/#h-processing-model\">loadEvent</a></th>";
      }
      $out .= '</tr>';
      $out .= '<tr>';
      if ($this->hasUserTiming)
        foreach($this->userTimings[0] as $label => $value)
          $out .= '<td>' . htmlspecialchars($value) . '</td>';
      if ($this->hasNavTiming) {
        $out .= "<td$borderClass>";
        if ($data['firstPaint'] > 0)
          $out .= number_format($data['firstPaint'] / 1000.0, 3) . 's</td><td>';
        if (isset($data['domInteractive']) && $data['domInteractive'] > 0)
          $out .= number_format($data['domInteractive'] / 1000.0, 3) . 's</td><td>';
        $out .= number_format($data['domContentLoadedEventStart'] / 1000.0, 3) . 's - ' .
          number_format($data['domContentLoadedEventEnd'] / 1000.0, 3) . 's (' .
          number_format(($data['domContentLoadedEventEnd'] - $data['domContentLoadedEventStart']) / 1000.0, 3) . 's)' . '</td>';
        $out .= '<td>' . number_format($data['loadEventStart'] / 1000.0, 3) . 's - ' .
          number_format($data['loadEventEnd'] / 1000.0, 3) . 's (' .
          number_format(($data['loadEventEnd'] - $data['loadEventStart']) / 1000.0, 3) . 's)' . '</td>';
      }
      $out .= '</tr>';
      $out .= '</table><br>';
      return $out;
    }
  }

  private function _initUserTimings() {
    $userTimings = array();
    $userMetrics = array();

    foreach ($this->runResults->getStepResults() as $stepResult) {
      $stepUserTimings = $this->_userTimingsForStep($stepResult);
      $userTimings[] = $stepUserTimings;
      $userMetrics = array_merge($userMetrics, array_keys($stepUserTimings));
    }
    $userMetrics = array_unique($userMetrics);
    $defaultValues = array_combine($userMetrics, array_fill(0, count($userMetrics), "-"));

    $this->userTimings = array();
    foreach ($userTimings as &$stepUserTimings) {
      $this->userTimings[] = array_merge($defaultValues, $stepUserTimings);
    }
    return count($userMetrics) > 0;
  }

  private function _userTimingsForStep($stepResult) {
    $data = $stepResult->getRawResults();
    $userTimings = array();
    foreach($data as $metric => $value)
      if (substr($metric, 0, 9) == 'userTime.')
        $userTimings[substr($metric, 9)] = number_format($value / 1000, 3) . 's';
    if (isset($data['custom']) && count($data['custom'])) {
      foreach($data['custom'] as $metric) {
        if (isset($data[$metric])) {
          $value = $data[$metric];
          if (is_double($value))
            $value = number_format($value, 3, '.', '');
          $userTimings[$metric] = $value;
        }
      }
    }
    return $userTimings;
  }
}

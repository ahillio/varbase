<?php

namespace Drupal\charts_highcharts\Settings\Highcharts;

class PlotOptions implements \JsonSerializable {
  private $plotType;

  /**
   * @return mixed
   */
  public function getPlotType() {
    return $this->plotType;
  }

  /**
   * @param mixed $plotType
   */
  public function setPlotType($plotType) {
    $this->plotType = $plotType;
  }

  /**
   * @return array
   */
  public function jsonSerialize() {
    $vars = get_object_vars($this);

    return $vars;
  }

}

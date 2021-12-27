<?php
class WideAngleConfig {
  public $siteId;
  public $ignoreHash;
  public $trackerDomain;
  public $exclusionString;
  public $includeParamsString;
  private $helpers;

  public function __construct($siteId, $trackerDomain, $ignoreHash, $exclusionString, $includeParamsString) {
    $this->siteId = $siteId;
    $this->trackerDomain = $trackerDomain;
    $this->ignoreHash = $ignoreHash;
    $this->exclusionString = $exclusionString;
    $this->includeParamsString = $includeParamsString;
    $this->helpers = new WideAngleHelpers();
  }

  function generateHeaderScript() {
    $script = <<<EOD
<link href="{$this->trackerDomain}/script/{$this->siteId}.js" ref="prefetch"/>
EOD;
    return $script;
  }

  function generateFooterScript() {
    $pathExlusionsAttribute = $this->generateExclusionsAttribute();
    $includeParamsAttribute = $this->generateIncludeParamsAttribute();

    $script = <<<EOD
<script async defer
  src="{$this->trackerDomain}/script/{$this->siteId}.js"
  data-waa-ignore-hash="{$this->ignoreHash}"
  $includeParamsAttribute
  $pathExlusionsAttribute></script>
EOD;
    return preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $script);
  }

  private function generateIncludeParamsAttribute() {
    $params = $this->helpers->parseIncludeParamsSetting($this->includeParamsString);
    if(sizeof($params) > 0) {
      return "data-waa-inc-params=\"" . implode(",", $params) . "\"";
    }
    return "";
  }

  private function generateExclusionsAttribute() {
    $pathExlusionsAttribute = "";
    $exclusions = $this->helpers->parseExclusionSetting($this->exclusionString);
    if(sizeof($exclusions) > 0) {
      $pathExlusionsAttribute = $this->generateExclusionsAttributeValue($exclusions);
    }

    $pathExlusionsAttributeWithKey = "";
    if(trim($pathExlusionsAttribute) != "") {
      $pathExlusionsAttributeWithKey = "data-waa-exc-paths=\"" . $pathExlusionsAttribute ."\"";
    }
    return $pathExlusionsAttributeWithKey;
  }

  private function generateExclusionsAttributeValue($exclusions) {
    $accumulator = array();
    foreach($exclusions as $exclusion) {
      switch($exclusion->get_type()) {
        case "start":
          array_push($accumulator, "^" .  preg_quote($exclusion->get_value()) . ".*");
          break;
        case "end":
          array_push($accumulator, ".*" .  preg_quote($exclusion->get_value()) . "$");
          break;
        case "regex":
          array_push($accumulator, $exclusion->get_value());
          break;
      }
    }
    return implode(",", $accumulator);
  }
}
?>
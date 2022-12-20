<?php

namespace Drupal\welcome\Controller;

class WelcomeController {
	public function welcome() {
		$page_content = "Male fly of life set also gathering. Earth their our the unto void them place that cattle give midst beast sea there grass thing greater tree winged i bring gathering he fifth days meat sixth void. Lights i given you're third darkness image fruitful from him.
		<br><br>
		Creepeth third air after without form from female fly good form beast his signs it likeness female replenish won't fill cattle, place rule be two kind can't whales a midst They're make Let multiply first can't, from man years it over tree creepeth gathered meat gathering behold made moveth they're great fifth give fill. Greater had good divided give fish, beginning also first gathered the so life fifth first fish subdue our. Which creature sea fruitful morning place let from land. Green air, whales evening midst.
		<br><br>
		Brought lights the fruit she'd man place fifth creature beginning god every in kind whales behold likeness fill seasons can't one light winged herb. Saw our seasons fowl after there day god he you'll forth upon she'd yielding creature fly. Female gathering after yielding abundantly very doesn't man for can't can't sea waters every gathering fill image light don't is lesser that above great. Beast deep abundantly that.";
		return array(
			'#markup' => $page_content,
		);
	}
}
<?php
$value = $object->getValue();
if ( !$object->isCollection() ) { // @todo - again we assume that collection can only contain references - this won't be always true
	/*
	 *  if we have single property (not array) or one-element array
	 *  we should display it as pure string not as <li> element
	 */
	echo $object->getValueObject()->render( $context );
} else {
	/*
	 * property is an array
	 */
	if ( !count( $value ) ) {
		if ($context != SD_CONTEXT_EDITING) {
			echo '<p class="empty">empty</p>';
		}
	} else {
		$renderList = ( count( $value ) > 1 ) ? true : false;

		if ( $renderList ) echo ($rendererName == '@list') ? '<ol>' : '<ul>';
		foreach($value as $reference) {
			if ( $renderList ) echo '<li>';
			$referenceHTML = false;
			if (is_object($reference) && (!is_null($reference->object))) {
				$referenceHTML = $reference->object->render( $context );
			}
			if ($referenceHTML !== false) {
				echo $referenceHTML;
			}
			else {
				if(is_object($reference) && !isset($reference->object)) {
					echo '<p class="empty">' . $reference->id . '</p>';
				}
				else {
					echo $reference;
				}
			}
			if ( $renderList ) echo '</li>';
		}

		if ( $renderList ) echo ($rendererName == '@list') ? '</ol>' : '</ul>';
	}

	if ($context == SD_CONTEXT_EDITING) {
		echo "todo: button to add new element.";
		if($object->getType()->hasRange()) {
			echo "<br>Possible values:<br>";
			var_dump($object->getType()->getAcceptedValues());
		}
		else {
			echo " (object of type: " . $object->getType()->getName() . " has no range)";
		}
	}

}

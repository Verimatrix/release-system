<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var  \Akeeba\ReleaseSystem\Site\View\Releases\Html $this */
$js = <<<JS
akeeba.System.documentReady(function(){
    akeeba.fef.tabs();

    var release_info = document.querySelectorAll('.release-info-toggler');

    release_info.forEach(function(item){
        item.addEventListener('click', function(){
            var target = this.getAttribute('data-target');
            var elTarget = document.getElementById(target);

            // If the element is visible, hide it
			if (window.getComputedStyle(elTarget).display === 'block') {
				elTarget.style.display = 'none';
			}
			else{
				elTarget.style.display = '';
			}
        })
    });
});
JS;

$this->getContainer()->template->addJSInline($js);
?>

<div class="item-page{{{ $this->params->get('pageclass_sfx') }}}">
	@if($this->params->get('show_page_heading'))
	<div class="page-header">
		<h1>
			{{{ $this->params->get('page_heading', $this->menu->title) }}}
		</h1>
	</div>
	@endif

	@include('site:com_ars/Releases/category', ['id' => $this->category->id, 'item' => $this->category, 'Itemid' => $this->Itemid, 'no_link' => true])

	<div class="ars-releases ars-releases-{{ $this->category->is_supported ? 'supported' : 'unsupported' }}">
	@if(count($this->items))
		@foreach($this->items as $item)
				@include('site:com_ars/Releases/release', ['item' => $item, 'Itemid' => $this->Itemid])
		@endforeach
	@else
		<div class="ars-noitems">
			@lang('ARS_NO_RELEASES')
		</div>
	@endif
	</div>
</div>

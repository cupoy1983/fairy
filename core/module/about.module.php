<?php
class AboutModule{
	public function about(){
		global $_FANWE;
		
		$cache_file = getTplCache('page/about/about');
		if(!@include($cache_file))
		{
			include template('page/about/about');
		}
		display($cache_file);
	}
	
	public function contact(){
		global $_FANWE;
		
		$cache_file = getTplCache('page/about/contact');
		if(!@include($cache_file))
		{
			include template('page/about/contact');
		}
		display($cache_file);
	}
	
	public function link(){
		global $_FANWE;
		
		$cache_file = getTplCache('page/about/link');
		if(!@include($cache_file))
		{
			include template('page/about/link');
		}
		display($cache_file);
	}
	
	public function help(){
		global $_FANWE;
		
		$cache_file = getTplCache('page/about/help');
		if(!@include($cache_file))
		{
			include template('page/about/help');
		}
		display($cache_file);
	}
	
	public function iphone(){
		global $_FANWE;
	
		$cache_file = getTplCache('page/about/iphone');
		if(!@include($cache_file))
		{
			include template('page/about/iphone');
		}
		display($cache_file);
	}
	
	public function android(){
		global $_FANWE;
	
		$cache_file = getTplCache('page/about/android');
		if(!@include($cache_file))
		{
			include template('page/about/android');
		}
		display($cache_file);
	}
}
?>
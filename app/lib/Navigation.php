<?php
/*
	Navigation stack to handle user breadcrums

	Is a stack that automatically returns to a previous position if a url is
	inserted twice. Expects the url to be an array
		[0] as name and
		[1] as url address

	Designed to be to passed to a twig template, the paths array is public
	for this reason.

	Author: Thomas Nixon
	Data: 21/11/2016
*/
class Navigation {
    public $paths = [];

    // Add a url to the path array
    public function push($url) {
        if ($this->url_exists($url)) {
            $this->revert($url);
        } else {
            $this->paths[] = $url;
        }
    }

    // Remove last item from path
    public function pop() {
        $result = null;
        $length = count($this->paths);
        if ($length < 1) {
            return false;
        }
        $result = $this->paths[$length - 1];
        unset($this->paths[$length - 1]);
        return $result;
    }

    // Reduce path back to an existing url
    public function revert($url) {
        while (end($this->paths)[0] != $url[0]) {
            $this->pop();
        }
    }

	// Check if a url exists already
    public function url_exists($url) {
        foreach ($this->paths as $key => $value) {
            if ($value[0] == $url[0]) {
				$this->paths[$key] = $url;
                return true;
            }
        }
        return false;
    }
}

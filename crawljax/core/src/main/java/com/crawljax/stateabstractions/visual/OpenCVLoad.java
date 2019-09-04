package com.crawljax.stateabstractions.visual;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

public class OpenCVLoad {

	private static final Logger LOG =
			LoggerFactory.getLogger(OpenCVLoad.class.getName());

	public static String location = null;
	public static boolean loaded = false;
	static {
		if(location==null) {
			LOG.error("Please set the location of OpenCV Library");
			System.exit(-1);
		}
		System.load(location);
		System.out.println("OPENCV LOADED!!");
	}
	public static boolean load() {
		if(!loaded) {
			loaded = true;
		}
		
		return true;
	}
	
}

package config;

import java.io.File;

public class Settings {

	public static String app = "crawl-petclinic-60min-DOM";
//	public static String app = "crawl-petclinic-60min-VISUAL";
//	public static final int CLUSTER_NUMBER = 11; // petclinic
	
	
//	public static String app = "crawl-addressbook-60min-DOM";
//	public static String app = "crawl-addressbook-60min-VISUAL";
//	public static final int CLUSTER_NUMBER = 15; // addressbook 18 DOM; 15 VISUAL
	
	
//	public static String app = "crawl-claroline-60min-DOM";
//	public static String app = "crawl-claroline-60min-VISUAL";
	public static final int CLUSTER_NUMBER = 11; // claroline 13 DOM; 14 VISUAL
	
	
//	public static String app = "crawl-phonecat-60min-DOM";
//	public static String app = "crawl-phonecat-60min-VISUAL";
//	public static final int CLUSTER_NUMBER = 2; // phonecat
	
	public static final boolean MODIFY_SCREENSHOTS = false;

	
	/*
	 * ******* DO NOT EDIT ANYTHING DOWN HERE *******
	 * **********************************************
	 */
	public static String sep = File.separator;
	public static String projectBaseFolder = "src" + sep + "main" + sep + "java" + sep;
	public static String resourcesFolder = "src" + sep + "main" + sep + "resources" + sep;
	public static String outputDir = "output" + sep;
	public static String outputDirApp = "output" + sep + app + sep;

	/* path to the test suite used as a reference. */
	public static String pathToScreenshots = resourcesFolder + app + sep + "screenshots" + sep;
	public static String pathToModifiedScreenshots = resourcesFolder + app + sep + "modifiedScreenshots" + sep;

	/* path to the test suite under test. */
	public static String pathToDoms = resourcesFolder + app + sep + "doms" + sep;

	/* path to states. */
	public static String pathToStates = resourcesFolder + app + sep + "states" + sep;

	/* path to clustering gold standard. */
	public static String pathToClusteringGS = outputDirApp + sep + "clustering-gold-standard.json";

	/* file extensions. */
	public static String JPG_EXT = ".jpg"; // jpg is used for small thumbnails
	public static String PNG_EXT = ".png"; // png is used for big screenshots
	public static String HTML_EXT = ".html";
	public static String JAVA_EXT = ".java";
	public static String JSON_EXT = ".json";
	public static String CSV_EXT = ".csv";

	/* regexp. */
	public static final String[] TAGS_BLACKLIST = new String[] { "head", "script", "link", "meta", "style", "canvas" };
	public static final String[] ATTRIBUTES_WHITELIST = new String[] { "id", "name", "class", "title", "alt", "value" };

	public final static String REGEX_FOR_GETTING_ID = "\\*\\[@id=['|\"]?(.+[^'\"])['|\"]?\\]";
	public final static String REGEX_FOR_GETTING_INDEX = "\\[(.+)\\]";

	public static final String clusteringScriptPath = System.getProperty("user.dir") + sep + "clustering.r";

	public static boolean RETAIN_ONLY_STATES = true;

//	public static final String pathToRscript = "/usr/bin/Rscript "; // Mac 10.10
//	public static final String pathToRscript = "/usr/bin/local/Rscript "; // Mac 10.13
	public static final String pathToRscript = "/Library/Frameworks/R.framework/Resources/bin/Rscript ";
	
	public static boolean VERBOSE = true;
	public static boolean PROGRESS = true;


	public static boolean CLUSTERING_ENABLED = false;

///////////////////////////////////////////////////////////////////	
// For RQ2 : Comparing the current crawl with original max crawl
	public static String gsLocation = "";
	public static String maxCrawl= "";
	public static String maxCrawl_doms = "";
	public static String maxCrawl_screenshots="";
	public static String maxCrawl_states = "";
	public static String gsJson = "";

	public static String resultJson; // For the crawl being compared 
//////////////////////////////////////////////////////////////////



	public static final String OUTPUT_DIRECTORY_NAME = "comp_output";

	/* comparators to execute. */
	public static enum Comparators {
		DOMONLY, VISUALONLY, ALL
	}

}

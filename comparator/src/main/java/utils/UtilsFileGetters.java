package utils;

import java.io.File;
import java.io.FileNotFoundException;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.HashSet;
import java.util.List;
import java.util.Set;

import com.google.common.collect.Sets;

import config.Settings;

public class UtilsFileGetters {

	/**
	 * get all the screenshots (i.e., jpg files) from a Crawljax crawl
	 * 
	 * @param directory
	 *            Crawljax's output directory name
	 * @return
	 */
	public static List<File> getScreenshots(String directory) {

		File[] screenshots = new File(directory).listFiles(FileFilters.screenshotsFilter);
		if(Settings.RETAIN_ONLY_STATES)
			return retainOnlyStates(screenshots);
		else
			return Arrays.asList(screenshots);

	}

	/**
	 * get all the DOM files (i.e., html files) from a Crawljax crawl
	 * 
	 * @param directory
	 *            Crawljax's output directory name
	 * @return
	 */
	public static List<File> getDoms(String directory) {

		File[] doms = new File(directory).listFiles(FileFilters.htmlFilesFilter);
		if(Settings.RETAIN_ONLY_STATES) {
			return retainOnlyStates(doms);
		}
		else {
			return Arrays.asList(doms);
		}
	}

	private static List<File> retainOnlyStates(File[] files) {

		File[] states = new File(Settings.pathToStates).listFiles(FileFilters.htmlFilesFilter);
		
		Set<String> stateSet = new HashSet<String>();
		for (int i = 0; i < states.length; i++) {
			stateSet.add(states[i].getName().replaceAll(".html", ""));
		}

		Set<String> fileSet = new HashSet<String>();
		for (int i = 0; i < files.length; i++) {
			fileSet.add(files[i].getName().replaceAll(".html", "").replaceAll(".png", ""));
		}

		Set<String> intersection = Sets.intersection(stateSet, fileSet);

		List<File> retained = new ArrayList<File>();
		for (int i = 0; i < files.length; i++) {
			if (intersection.contains(files[i].getName().replaceAll(".html", "").replaceAll(".png", ""))) {
				retained.add(files[i]);
			}
		}

		return retained;
	}

	/**
	 * get all the CSV files (i.e., csv files) created by comparators
	 * 
	 * @param directory
	 *            output/<crawl-name>
	 * @return
	 */
	public static List<File> getCSVs(String directory) {

		File[] files = new File(directory).listFiles(FileFilters.csvFilter);
		return Arrays.asList(files);
	}

	/**
	 * 
	 * @param outputDir
	 * @return
	 * @throws FileNotFoundException
	 */
	public static String getClusterFile(File outputDir) throws FileNotFoundException {

		String[] clusteringFiles = outputDir.list(FileFilters.clusterFileFilter);
		if (clusteringFiles.length == 0)
			return null;
		String outputDirPath = outputDir.getAbsolutePath();

		if (!outputDirPath.endsWith(Settings.sep))
			outputDirPath += Settings.sep;

		// TODO: return a list of files instead
		String clusterFilePath = outputDirPath + clusteringFiles[0];

		return clusterFilePath;
	}

	/**
	 * 
	 * @param outputDir
	 * @return
	 * @throws FileNotFoundException
	 */
	public static List<String> getAllClusterFiles(File outputDir) throws FileNotFoundException {

		String[] clusteringFiles = outputDir.list(FileFilters.clusterFileFilter);
		List<String> clusterFileList = new ArrayList<String>();

		if (clusteringFiles.length == 0)
			return null;

		String outputDirPath = outputDir.getAbsolutePath();

		if (!outputDirPath.endsWith(Settings.sep))
			outputDirPath += Settings.sep;

		for (String clusterFile : clusteringFiles) {
			clusterFileList.add(outputDirPath + clusterFile);
		}

		return clusterFileList;
	}

}

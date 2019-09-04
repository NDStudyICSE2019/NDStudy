package runner;

import java.io.BufferedReader;
import java.io.File;
import java.io.FileNotFoundException;
import java.io.IOException;
import java.io.InputStreamReader;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

import org.opencv.core.Core;

import com.google.common.io.Files;

import comparators.DomAbstractComparator;
import comparators.VisualAbstractComparator;
import comparators.dom.DomComparators;
import comparators.visual.VisualComparators;
import computator.DomSimilarity;
import computator.VisualSimilarity;
import config.Settings;
import config.Settings.Comparators;
import utils.ClusteringUtils;
import utils.CsvUtils;
import utils.HungarianAlgorithm;
import utils.ImageUtils;
import utils.Jaccard;
import utils.Utils;
import utils.UtilsFileGetters;

public class Main {

	static {
		System.out.println(System.getProperty("java.library.path"));
		//System.loadLibrary(Core.NATIVE_LIBRARY_NAME);
		System.out.println(Core.NATIVE_LIBRARY_NAME);
		String LIBRARY_LOCATION = null;
		if(LIBRARY_LOCATION == null) {
			System.err.println("Please set the opencv library to load");
			System.err.println("runner/Main.java");
			System.exit(-1);
		}
		System.load(LIBRARY_LOCATION);
		
	}


	static Comparators comparators = Comparators.ALL;

	public static void main(String[] args) throws IOException {

		System.out.println(System.getProperty("java.library.path"));
		String crawlPath = args[0];
		String crawlFolderPath = "";

		if (crawlPath.endsWith(Settings.sep)) {
			Settings.outputDirApp = crawlPath + Settings.OUTPUT_DIRECTORY_NAME + Settings.sep;
			crawlFolderPath = crawlPath;
		} else {
			Settings.outputDirApp = crawlPath + Settings.sep + Settings.OUTPUT_DIRECTORY_NAME + Settings.sep;
			crawlFolderPath = crawlPath + Settings.sep;
		}
		
		Settings.pathToDoms =  crawlFolderPath + "doms";
		Settings.pathToScreenshots = crawlFolderPath + "screenshots";
		Settings.pathToStates = crawlFolderPath + "states";
		Settings.app = args[1];
		generateAllData();
		new File(Settings.outputDirApp + "success.txt").createNewFile();
		return;
	}

	public static void generateData(String crawlPath, VisualAbstractComparator vcomp, DomAbstractComparator dcomp,
			boolean fromCrawljax) throws IOException {

		String strategy = "";

		if (fromCrawljax) {

			if (crawlPath.endsWith(Settings.sep))
				Settings.outputDirApp = crawlPath;
			else
				Settings.outputDirApp = crawlPath + Settings.sep;

			Settings.pathToDoms = Settings.outputDirApp + "doms";
			Settings.pathToScreenshots = Settings.outputDirApp + "screenshots";

			Settings.app = new File(Settings.outputDirApp).getName();
		}

		if (vcomp != null) {

			// read all screenshot files.
			List<File> screenshots = UtilsFileGetters.getScreenshots(Settings.pathToScreenshots);
			Utils.sortStatesByNaturalOrdering(screenshots);
			// Utils.printStateList(screenshots);

			// System.out.println("VISUAL COMPARATORS");

			System.out.println("Computing " + vcomp.getName());

			VisualSimilarity sim = new VisualSimilarity(screenshots, vcomp);
			sim.computeSimilarity();
			strategy = vcomp.getName();

		}

		if (dcomp != null) {

			// read all DOM files.
			List<File> doms = UtilsFileGetters.getDoms(Settings.pathToDoms);
			Utils.sortStatesByNaturalOrdering(doms);

			// System.out.println("DOM COMPARATORS");

			System.out.println("Computing " + dcomp.getName());

			DomSimilarity sim = new DomSimilarity(doms, dcomp);
			sim.computeSimilarity();

			strategy = dcomp.getName();
		}

		if (Settings.CLUSTERING_ENABLED) {
			try {

				String outputDirString = new File(Settings.outputDirApp).getAbsolutePath();

				if (!outputDirString.endsWith(Settings.sep))
					outputDirString = outputDirString + Settings.sep;

				String line;
				Process p = Runtime.getRuntime().exec(Settings.pathToRscript + Settings.clusteringScriptPath + " "
						+ outputDirString + " " + strategy + " " + Settings.CLUSTER_NUMBER);

				BufferedReader in = new BufferedReader(new InputStreamReader(p.getInputStream()));
				while ((line = in.readLine()) != null) {
					System.out.println(line);
				}

				in.close();
				p.waitFor();

			} catch (Exception e) {
				System.out.println("Error processing R script");
			}
		}

	}

	public static void generateAllData() {

		Utils.createOutputDirectories();

		System.out.println("Application " + Settings.app);

		if (comparators == Comparators.VISUALONLY || comparators == Comparators.ALL) {

			/* read all screenshot files. */
			List<File> screenshots = null;
			if (Settings.MODIFY_SCREENSHOTS) {
				// ImageUtils.modifyAllScreenshots();
				screenshots = UtilsFileGetters.getScreenshots(Settings.pathToModifiedScreenshots);
			} else {
				screenshots = UtilsFileGetters.getScreenshots(Settings.pathToScreenshots);
			}
			Utils.sortStatesByNaturalOrdering(screenshots);

			System.out.println("VISUAL COMPARATORS");

			/* iterate over all visual classes. */
			for (VisualAbstractComparator abs : VisualComparators.getComparators()) {

				try {
					generateData(Settings.outputDirApp, abs, null, false);
				} catch (IOException e) {
					// TODO Auto-generated catch block
					e.printStackTrace();
				}

			}

		}

		if (comparators == Comparators.DOMONLY || comparators == Comparators.ALL) {

			/* read all DOM files. */
			List<File> doms = UtilsFileGetters.getDoms(Settings.pathToDoms);
			Utils.sortStatesByNaturalOrdering(doms);

			System.out.println("DOM COMPARATORS");

			/* iterate over all DOM classes. */
			for (DomAbstractComparator abs : DomComparators.getComparators()) {

				try {
					generateData(Settings.outputDirApp, null, abs, false);
				} catch (IOException e) {
					e.printStackTrace();
				}
			}
		}

		if (Settings.CLUSTERING_ENABLED) {
			Map<String, Integer> pedBestMap = new HashMap<String, Integer>();
			Map<String, Integer> pedOneToOneMap = new HashMap<String, Integer>();

			try {
				/* read the clustering GS. */
				String[][] clusteringGS = ClusteringUtils.readClusterFile(Settings.pathToClusteringGS);

				/* for each cluster file, format it and get the PED. */
				List<String> clusterFiles = UtilsFileGetters.getAllClusterFiles(new File(Settings.outputDirApp));
				for (String file : clusterFiles) {

					String[][] clustering = ClusteringUtils.readClusterFile(file);

					if (clusteringGS.length != clustering.length) {
						throw new Exception(
								"Different number of clusters! Check CLUSTER_NUMBER in Settings and/or the gold standard");
					}

					System.out.println(Files.getNameWithoutExtension(file));
					double[][] matrix = Jaccard.calculateJaccard(clusteringGS, clustering, true);

					HungarianAlgorithm ha = new HungarianAlgorithm(matrix);
					int[] result = ha.execute();

					for (int i = 0; i < result.length; i++)
						System.out.println(
								String.format("Row%d => Col%d (%f)", i + 1, result[i] + 1, matrix[i][result[i]])); // Rowi
																													// =>
																													// Colj
																													// (value)

					int ped = ClusteringUtils.getPartitionEditDistance(clusteringGS, clustering, result, true);
					System.out.println("PED-best(" + Files.getNameWithoutExtension(file) + "): " + ped);

					pedBestMap.put(Files.getNameWithoutExtension(file), ped);

					ped = 0;
					ped = ClusteringUtils.getPartitionEditDistance(clusteringGS, clustering, result, false);
					System.out.println("PED(" + Files.getNameWithoutExtension(file) + "): " + ped + "\n");

					pedOneToOneMap.put(Files.getNameWithoutExtension(file), ped);

				}

				CsvUtils.writePedCsvFile(pedBestMap, "PED-best");
				CsvUtils.writePedCsvFile(pedOneToOneMap, "PED-oneToOne");

			} catch (FileNotFoundException e) {
				e.printStackTrace();
			} catch (Exception e) {
				e.printStackTrace();
			}
		}
	}

}

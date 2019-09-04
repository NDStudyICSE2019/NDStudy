package utils;

import java.awt.Color;
import java.io.File;
import java.io.IOException;
import java.io.StringReader;
import java.math.BigDecimal;
import java.nio.file.Files;
import java.util.Collections;
import java.util.LinkedHashMap;
import java.util.LinkedList;
import java.util.List;
import java.util.Map;

import org.cyberneko.html.parsers.DOMParser;
import org.w3c.dom.Document;
import org.w3c.dom.Element;
import org.w3c.dom.Node;
import org.w3c.dom.traversal.DocumentTraversal;
import org.w3c.dom.traversal.NodeFilter;
import org.w3c.dom.traversal.TreeWalker;
import org.xml.sax.InputSource;
import org.xml.sax.SAXException;

import config.Settings;
import utils.treeEdit.LblTree;
import utils.treeEdit.RTED_InfoTree_Opt;

public class Utils {

	public static void sortStatesByNaturalOrdering(List<File> list) {
		if(list==null){
			System.out.println("a null list provided to sort !! Exiting ");
			System.exit(-1);
		}

		Collections.sort(list, new NaturalOrderComparator());

	}

	public static void printStateList(List<File> list) {

		System.out.println("# of states: " + list.size());

		System.out.print("[");

		for (int i = 0; i < list.size(); i++) {
			System.out.print(list.get(i).getName().replaceAll(".png", ""));

			if (i == list.size() - 1) {
				System.out.println("]");
			} else {
				System.out.print(", ");
			}
		}

	}

	public static void createHeatChart(String string, double[][] dm) {

		HeatChart chart = new HeatChart(dm);

		/* Customise the chart. */
		chart.setTitle(Settings.app + " heatmap chart" + string);
		chart.setXAxisLabel("X Axis");
		chart.setYAxisLabel("Y Axis");
		chart.setHighValueColour(Color.RED);
		chart.setLowValueColour(Color.BLUE);

		/* Output the chart to a file. */
		String heatmapFile = Settings.outputDirApp + Settings.app + "_" + string + "-heatmap.png";
		try {
			chart.saveToFile(new File(heatmapFile));
		} catch (IOException e) {
			e.printStackTrace();
		}

		// System.out.println("Heatmap created: " + heatmapFile);
	}
	
	public static double getRobustTreeEditDistanceRaw(LblTree domTree1, LblTree domTree2) {
		RTED_InfoTree_Opt rted;
		double ted;

		rted = new RTED_InfoTree_Opt(1, 1, 1);

		// compute tree edit distance
		rted.init(domTree1, domTree2);
		rted.computeOptimalStrategy();
		ted = rted.nonNormalizedTreeDist();
		return ted;
	}

	/**
	 * Get a scalar value for the DOM diversity using the Robust Tree Edit Distance
	 * 
	 * @param dom1
	 * @param dom2
	 * @return
	 * @throws IOException
	 */
	public static double getRobustTreeEditDistance(String dom1, String dom2) {

		double DD = 0.0;
		LblTree domTree1 = null, domTree2 = null;
		try {
			domTree1 = getDomTree(dom1);
			domTree2 = getDomTree(dom2);
		} catch (IOException e) {
			e.printStackTrace();
		}
		
		//int maxSize = Math.max(domTree1.getNodeCount(), domTree2.getNodeCount());
		int sumSize = domTree1.getNodeCount() + domTree2.getNodeCount();
		double ted = getRobustTreeEditDistanceRaw(domTree1, domTree2);
		ted /= (double) sumSize;

		DD = ted;
		return DD;
	}

	public static LblTree getDomTree(String dom1) throws IOException {

		org.w3c.dom.Document doc1 = asDocument(dom1);

		LblTree domTree = null;

		DocumentTraversal traversal = (DocumentTraversal) doc1;
		TreeWalker walker = traversal.createTreeWalker(doc1.getDocumentElement(), NodeFilter.SHOW_ELEMENT, null, true);
		domTree = createTree(walker);

		return domTree;
	}

	/**
	 * transforms a string into a Document object.
	 * 
	 * @param html
	 *            the HTML string.
	 * @return The DOM Document version of the HTML string.
	 * @throws IOException
	 *             if an IO failure occurs.
	 * @throws SAXException
	 *             if an exception occurs while parsing the HTML string.
	 */
	public static Document asDocument(String html) throws IOException {
		DOMParser domParser = new DOMParser();
		try {
			domParser.setProperty("http://cyberneko.org/html/properties/names/elems", "match");
			domParser.setFeature("http://xml.org/sax/features/namespaces", false);
			domParser.parse(new InputSource(new StringReader(html)));
		} catch (SAXException e) {
			throw new IOException("Error while reading HTML: " + html, e);
		}
		return domParser.getDocument();
	}

	/**
	 * Recursively construct a LblTree from DOM tree
	 *
	 * @param walker
	 *            tree walker for DOM tree traversal
	 * @return tree represented by DOM tree
	 */
	private static LblTree createTree(TreeWalker walker) {
		Node parent = walker.getCurrentNode();
		LblTree node = new LblTree(((Element) parent).getNodeName(), -1); // treeID = -1
		for (Node n = walker.firstChild(); n != null; n = walker.nextSibling()) {
			node.add(createTree(walker));
		}
		walker.setCurrentNode(parent);
		return node;
	}

	public static void createOutputDirectories() {

		if (!Files.exists(new File(Settings.outputDir).toPath())) {
			try {
				Files.createDirectory(new File(Settings.outputDir).toPath());
			} catch (IOException e) {
				e.printStackTrace();
			}

		}

		if (!Files.exists(new File(Settings.outputDirApp).toPath())) {
			try {
				Files.createDirectory(new File(Settings.outputDirApp).toPath());
			} catch (IOException e) {
				e.printStackTrace();
			}
		}

	}

	/**
	 * print out the distance map
	 * 
	 * @param map
	 */
	public static void printMap(Map<String, LinkedHashMap<String, BigDecimal>> map) {

		System.out.print("keys: " + map.size() + ", values: ");

		for (String s : map.keySet()) {
			System.out.println(map.get(s).size());
			break;
		}

		for (String s : map.keySet()) {
			System.out.println(s);
			System.out.println("\t" + map.get(s));
		}

		System.out.println();

	}

	/**
	 * print a generic map
	 * 
	 * @param map
	 */
	public static void printHashMap(Map<Integer, LinkedList<String>> map) {

		System.out.print("keys: " + map.size() + ", values: ");

		for (Integer s : map.keySet()) {
			System.out.println(map.get(s).size());
			break;
		}

		for (Integer s : map.keySet()) {
			System.out.println(s);
			System.out.println("\t" + map.get(s));
		}

		System.out.println();

	}

	public static Map<String, LinkedHashMap<String, BigDecimal>> convertToDistanceMap(double[][] distanceMatrix, List<File> doms) {

		Map<String, LinkedHashMap<String, BigDecimal>> distanceMap = new LinkedHashMap<String, LinkedHashMap<String, BigDecimal>>();

		for (int i = 0; i < doms.size(); i++) {

			LinkedHashMap<String, BigDecimal> distanceVector = new LinkedHashMap<String, BigDecimal>();

			for (int j = 0; j < doms.size(); j++) {

				/* read the similarity measure. */
				BigDecimal bd = new BigDecimal(distanceMatrix[i][j]);

				distanceVector.put(doms.get(j).getName(), bd);

			}

			distanceMap.put(doms.get(i).getName(), distanceVector);
		}

		return distanceMap;

	}

	/**
	 * This method implements the Levenshtein Distance algorithm between two strings
	 * 
	 * @param s0
	 *            first string to be compared
	 * @param s1
	 *            second string to be compared
	 * @return the cost to turn s0 into s1
	 */
	public static int levenshteinDistance(String s0, String s1) {
		int len0 = s0.length() + 1;
		int len1 = s1.length() + 1;

		// the array of distances
		int[] cost = new int[len0];
		int[] newcost = new int[len0]; 

		// initial cost of skipping prefix in String s0
		for (int i = 0; i < len0; i++)
			cost[i] = i;
 

		// dynamically computing the array of distances

		// transformation cost for each letter in s1
		for (int j = 1; j < len1; j++) {
			// initial cost of skipping prefix in String s1
			newcost[0] = j;

			// transformation cost for each letter in s0
			for (int i = 1; i < len0; i++) {
				// matching current letters in both strings
				int match = (s0.charAt(i - 1) == s1.charAt(j - 1)) ? 0 : 1;

				// computing cost for each transformation
				int cost_replace = cost[i - 1] + match;
				int cost_insert = cost[i] + 1;
				int cost_delete = newcost[i - 1] + 1;

				// keep minimum cost
				newcost[i] = Math.min(Math.min(cost_insert, cost_delete), cost_replace);
			}

			// swap cost/newcost arrays
			int[] swap = cost;
			cost = newcost;
			newcost = swap;
		}

		// the distance is the cost for transforming all letters in both strings
		return cost[len0 - 1];
	}

	public static String getHexFromDecimal(int dec) {
		// return "#" + Integer.toHexString(dec);
		return String.format("#%06X", (0xFFFFFF & dec));
	}

	public static String getHexFromRGB(int red, int green, int blue) {
		return String.format("#%02x%02x%02x", red, green, blue);
	}

}

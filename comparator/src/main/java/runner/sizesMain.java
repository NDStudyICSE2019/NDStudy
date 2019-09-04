package runner;

import java.awt.image.BufferedImage;
import java.io.File;
import java.io.FileWriter;
import java.io.IOException;
import java.nio.charset.Charset;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;

import javax.imageio.ImageIO;

import org.apache.commons.io.FileUtils;
import org.apache.commons.io.FilenameUtils;

import com.google.gson.Gson;

import config.Settings;
import utils.DomUtils;
import utils.Utils;
import utils.UtilsFileGetters;
import utils.treeEdit.LblTree;

public class sizesMain {
	static boolean ignoreAlreadyCalculated = false;
	public static String getDomSizeJson() {
		
		List<File> doms = UtilsFileGetters.getDoms(Settings.pathToDoms);
		Utils.sortStatesByNaturalOrdering(doms);
		HashMap<String, ArrayList<Integer>> domSizes = new HashMap<>();
		
		for(File dom: doms) {
			String name = FilenameUtils.getBaseName(dom.getName());
			
			int domSize= -1;
			int strippedDomSize = -1;
			int domStructureSize = -1;
			int domContentSize = -1;
			try {
				String domStr =  FileUtils.readFileToString(dom, Charset.defaultCharset());
				domSize = domStr.length();
				domStructureSize = DomUtils.getDOMWithoutContent(domStr).length();
				strippedDomSize = DomUtils.getStrippedDom(domStr).length();
				domContentSize = DomUtils.getDOMContent(domStr).length();
			} catch (IOException e) {
				// TODO Auto-generated catch block
				e.printStackTrace();
			} catch (Exception e) {
				// TODO Auto-generated catch block
				e.printStackTrace();
			}
			
			ArrayList<Integer> sizes = new ArrayList<>();
			sizes.add(domSize);
			sizes.add(strippedDomSize);
			sizes.add(domStructureSize);
			sizes.add(domContentSize);
	
			domSizes.put(name, sizes);
		}
		
		Gson gson = new Gson();
		String domSizeJson = gson.toJson(domSizes);
		return domSizeJson;
	}
	
	

	public static String getPixelSizeJson() {
		List<File> screenshots = UtilsFileGetters.getScreenshots(Settings.pathToScreenshots);
		Utils.sortStatesByNaturalOrdering(screenshots);
		HashMap<String, Integer> pixelSizes = getPixelSizes(screenshots);
		
		Gson gson = new Gson();
		String pixelSizeJson = gson.toJson(pixelSizes);
		return pixelSizeJson;
	}



	static HashMap<String, Integer> getPixelSizes(List<File> screenshots) {
		
		HashMap<String, Integer> pixelSizes = new HashMap<>();
		
		for(File screenshot: screenshots) {
			String name = FilenameUtils.getBaseName(screenshot.getName());
			BufferedImage imgA = null;
			try {
				imgA = ImageIO.read(screenshot);
				int width = imgA.getWidth();
				int height = imgA.getHeight();
				int size = width*height;
				pixelSizes.put(name, size);
			} catch (IOException e) {
				e.printStackTrace();
			}catch(Exception ex) {
				ex.printStackTrace();
			}

		}
		return pixelSizes;
	}
	
	public static void main(String[] args) throws IOException {

		System.out.println(System.getProperty("java.library.path"));
		String crawlPath = args[0];
		if(args.length ==2) {
			String setIgnore = args[1].trim();
			if(setIgnore.equalsIgnoreCase("true")) {
				ignoreAlreadyCalculated = true;
			}
			else if (setIgnore.equalsIgnoreCase("false")) {
				ignoreAlreadyCalculated = false;
				System.out.println("Will recalculate the sizes.");
			}
		}
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
		System.out.println("Args : " + crawlPath );
		
		
		
		File prevDomFile = new File(Settings.outputDirApp + "domSizes.json");
		
		if(prevDomFile.exists()) {
			if(ignoreAlreadyCalculated) {
				System.out.println("Ignoring already calculated app DomSizes : " + Settings.outputDirApp);
			}
			else {
				writeDomSizeJson();
			}
		}
		else{
			writeDomSizeJson();
		}
		
		

		File prevNodeFile = new File(Settings.outputDirApp + "nodeSizes.json");
		
		if(prevNodeFile.exists()) {
			if(ignoreAlreadyCalculated) {
				System.out.println("Ignoring already calculated app NodeSizes : " + Settings.outputDirApp);
			}
			else {
				writeNodeSizeJson();
			}
		}
		else{
			writeNodeSizeJson();
		}
		

		File prevPixelFile = new File(Settings.outputDirApp + "pixelSizes.json");
		
		if(prevPixelFile.exists()) {
			if(ignoreAlreadyCalculated) {
				System.out.println("Ignoring already calculated app PixelSizes: " + Settings.outputDirApp);
			}
			else {
				writePixelSizeJson();
			}
		}
		else{
			writePixelSizeJson();
		}
		
		
		return;
	}


	@SuppressWarnings("deprecation")
	private static HashMap<String, Integer> getRTEDNodeSize(HashMap<String, Integer> nodeSizes, HashMap<String, LblTree> trees) {
		List<File> doms = UtilsFileGetters.getDoms(Settings.pathToDoms);
		Utils.sortStatesByNaturalOrdering(doms);
		
		for(File dom: doms) {
			String name = FilenameUtils.getBaseName(dom.getName());
			String dom1 = null;
			try {
				dom1 = FileUtils.readFileToString(dom);
				LblTree domTree1 = Utils.getDomTree(dom1);
				int count = domTree1.getNodeCount();
				nodeSizes.put(name, count);
				trees.put(name,  domTree1);
			} catch (IOException e) {
				e.printStackTrace();
			}
		}
		return nodeSizes;
	}
	
	
	public static String getRTEDNodeSizeJson() {
		
		HashMap<String, LblTree> trees = new HashMap<>();
		
		HashMap<String, Integer> nodeSizes = new HashMap<>();
		getRTEDNodeSize(nodeSizes, trees);
		Gson gson = new Gson();
		String nodeSizeJson = gson.toJson(nodeSizes);
		return nodeSizeJson;
	}

	private static void writeNodeSizeJson() {
		String nodeSizeJson = getRTEDNodeSizeJson();
		
		try {
			File nodeSizeFile = new File(Settings.outputDirApp + "nodeSizes.json");
			FileWriter writer = new FileWriter(nodeSizeFile);
			writer.write(nodeSizeJson);
			writer.flush();
			writer.close();
		}
		catch(Exception ex) {
			ex.printStackTrace();
		}
	}



	private static void writePixelSizeJson() {
		String pixelSizeJson = getPixelSizeJson();
		
		try {
			File pixelSizeFile = new File(Settings.outputDirApp + "pixelSizes.json");
			FileWriter writer = new FileWriter(pixelSizeFile);
			writer.write(pixelSizeJson);
			writer.flush();
			writer.close();
		}
		catch(Exception ex) {
			ex.printStackTrace();
		}
	}



	private static void writeDomSizeJson() {
		String domSizeJson = getDomSizeJson();

		try {
			File domSizeFile = new File(Settings.outputDirApp + "domSizes.json");
			FileWriter writer = new FileWriter(domSizeFile);
			writer.write(domSizeJson);
			writer.flush();
			writer.close();
		}
		catch(Exception ex) {
			ex.printStackTrace();
		}
	}
}

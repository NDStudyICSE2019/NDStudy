package runner;

import java.io.File;
import java.io.FileWriter;
import java.io.IOException;
import java.nio.charset.Charset;
import java.nio.file.Files;
import java.nio.file.NoSuchFileException;
import java.nio.file.Path;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Collections;
import java.util.Comparator;
import java.util.HashMap;
import java.util.List;
import java.util.Locale;
import java.util.Map.Entry;
import java.util.stream.Collectors;

import org.apache.commons.io.FileUtils;
import org.apache.commons.io.FilenameUtils;
import org.apache.commons.lang3.StringUtils;

import com.crawljax.core.plugin.Plugin;
import com.crawljax.plugins.crawloverview.model.OutPutModel;
import com.crawljax.plugins.crawloverview.model.State;
import com.fasterxml.jackson.annotation.JsonAutoDetect;
import com.fasterxml.jackson.core.JsonGenerator;
import com.fasterxml.jackson.core.JsonParseException;
import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.databind.JsonMappingException;
import com.fasterxml.jackson.databind.JsonSerializer;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.fasterxml.jackson.databind.SerializationFeature;
import com.fasterxml.jackson.databind.SerializerProvider;
import com.fasterxml.jackson.databind.module.SimpleModule;
import com.fasterxml.jackson.datatype.guava.GuavaModule;
import com.google.common.collect.BiMap;
import com.google.common.collect.HashBiMap;
import com.google.common.collect.ImmutableMap;
import com.google.gson.Gson;
import com.google.gson.GsonBuilder;

import comparators.dom.RTEDComparator;
import config.Settings;
import utils.DomUtils;
import utils.GsonUtils;
import utils.JsonUtils;
import utils.Utils;
import utils.UtilsFileGetters;
import utils.GSJson.ClassificationJson;
import utils.GSJson.pair;
import utils.GSJson.state;

public class RQ2Main {
	private class CustomComparator implements Comparator<state> {
	   

		@Override
		public int compare(state o1, state o2) {
			int o1id = o1.getId();
			int o2id = o2.getId();
			return o1id-o2id;
		}
	}
	private static final ObjectMapper MAPPER;
	static {
	MAPPER = new ObjectMapper();
	MAPPER.getSerializationConfig().getDefaultVisibilityChecker()
	        .withFieldVisibility(JsonAutoDetect.Visibility.ANY)
	        .withGetterVisibility(JsonAutoDetect.Visibility.NONE)
	        .withSetterVisibility(JsonAutoDetect.Visibility.NONE)
	        .withCreatorVisibility(JsonAutoDetect.Visibility.NONE);
	MAPPER.disable(SerializationFeature.FAIL_ON_EMPTY_BEANS);

	MAPPER.setDateFormat(new SimpleDateFormat("yyyy-MM-dd HH:mm:ss z", Locale.getDefault()));

	MAPPER.registerModule(new GuavaModule());
	SimpleModule testModule = new SimpleModule("Plugin serialiezr");
	testModule.addSerializer(new JsonSerializer<Plugin>() {

		@Override
		public void serialize(Plugin plugin, JsonGenerator jgen,
		        SerializerProvider provider) throws IOException, JsonProcessingException {
			jgen.writeString(plugin.getClass().getSimpleName());
		}

		@Override
		public Class<Plugin> handledType() {
			return Plugin.class;
		}
	});

	MAPPER.registerModule(testModule);

	}
	
	static HashMap<String, String> mapping =null;
	
	static HashMap<String, String> inverseMapping = null;

	private static appName currAppName = null; 
	
	static enum appName{
		petclinic, addressbook, claroline, unknown, dimeshift, mrbs, pagekit, ppma, phoenix, mantisbt
	}
	
	
	
	public static boolean isEqual(String dom1, String dom2) {
		boolean returnEqual = false;
		switch(currAppName) {
		case petclinic:
			dom1 = DomUtils.getStrippedDom(dom1);
			dom1= DomUtils.getDOMContent(dom1);
			dom1 = DomUtils.removeSessionData(dom1);

			dom2 = DomUtils.getStrippedDom(dom2);
			dom2= DomUtils.getDOMContent(dom2);
			dom2 = DomUtils.removeSessionData(dom2);
			break;
		case addressbook:
			dom1 = DomUtils.getStrippedDom(dom1);
//			dom1= DomUtils.getDOMContent(dom1);
			dom2 = DomUtils.getStrippedDom(dom2);
//			dom2= DomUtils.getDOMContent(dom2);
			break;
		case claroline:
			dom1 = DomUtils.getStrippedDom(dom1);
//			dom1= DomUtils.getDOMContent(dom1);
			dom2 = DomUtils.getStrippedDom(dom2);
//			dom2= DomUtils.getDOMContent(dom2);
			break;
		case dimeshift:
			dom1 = DomUtils.getStrippedDom(dom1);
//			dom1= DomUtils.getDOMContent(dom1);
			dom2 = DomUtils.getStrippedDom(dom2);
//			dom2= DomUtils.getDOMContent(dom2);
//			
//			double dist = Utils.getRobustTreeEditDistance(dom1, dom2);
//			if(dist==0) {
//				return true;
//			}
			break;
		
		case mrbs:
			dom1 = DomUtils.removeHiddenInputs(dom1);
			dom2 = DomUtils.removeHiddenInputs(dom2);
			dom1 = DomUtils.getStrippedDom(dom1);
			dom2 = DomUtils.getStrippedDom(dom2);
			break;
		case pagekit:
			dom1 = DomUtils.getStrippedDom(dom1);
			dom1 = DomUtils.removeHiddenInputs(dom1);

//			dom1= DomUtils.getDOMContent(dom1);
			dom2 = DomUtils.getStrippedDom(dom2);
			dom2 = DomUtils.removeHiddenInputs(dom2);

//			dom2= DomUtils.getDOMContent(dom2);
//			
//			double dist = Utils.getRobustTreeEditDistance(dom1, dom2);
//			if(dist==0) {
//				return true;
//			}
			break;
		case phoenix:
			dom1 = DomUtils.getStrippedDom(dom1);
//			dom1= DomUtils.getDOMContent(dom1);
			dom2 = DomUtils.getStrippedDom(dom2);
//			dom2= DomUtils.getDOMContent(dom2);
//			
//			double dist = Utils.getRobustTreeEditDistance(dom1, dom2);
//			if(dist==0) {
//				return true;
//			}
			break;
		case ppma:
			dom1 = DomUtils.getStrippedDom(dom1);
//			dom1= DomUtils.getDOMContent(dom1);
			dom2 = DomUtils.getStrippedDom(dom2);
//			dom2= DomUtils.getDOMContent(dom2);
//			
//			double dist = Utils.getRobustTreeEditDistance(dom1, dom2);
//			if(dist==0) {
//				return true;
//			}
			break;
		case mantisbt:
			dom1 = DomUtils.getStrippedDom(dom1);
			dom1 = DomUtils.removeHead(dom1);
			dom1 = DomUtils.removeElementsUnderXpath(dom1, "/html[1]/body[1]/table[1]/tbody[1]/tr[1]");
//			dom1= DomUtils.getDOMContent(dom1);
			dom2 = DomUtils.getStrippedDom(dom2);
			dom2 = DomUtils.removeHead(dom2);
			dom2 = DomUtils.removeElementsUnderXpath(dom2, "/html[1]/body[1]/table[1]/tbody[1]/tr[1]");
//			dom2= DomUtils.getDOMContent(dom2);
//			
//			double dist = Utils.getRobustTreeEditDistance(dom1, dom2);
//			if(dist==0) {
//				return true;
//			}
			break;
		default:
			dom1 = DomUtils.getStrippedDom(dom1);
			dom2 = DomUtils.getStrippedDom(dom2);
		}
		returnEqual = stringEquals(dom1, dom2);
		return returnEqual;
	}

	private static boolean stringEquals(String dom1, String dom2) {
		boolean returnEqual = dom1.equals(dom2);
		if(!returnEqual) {
//			System.out.println(StringUtils.difference(dom1, dom2));
		}
		return returnEqual;
	}
	
	private static ClassificationJson createNewObject(OutPutModel originalModel) {
		ClassificationJson gsJson = JsonUtils.readGsJson(Settings.gsJson);
		HashMap<String, state> gsStates = gsJson.getStates();
		HashMap<String, state> states = new HashMap<>();
		ImmutableMap<String, State> stateMap = originalModel.getStates();
		for(Entry<String, State> stateEntry: stateMap.entrySet()) {
			State state = stateEntry.getValue();
			int id = state.getId();
			String url = state.getUrl();
			String name= state.getName();
			String bin = "";
			ArrayList<String> clones = new ArrayList<String>();
			if(mapping.containsKey(name) && !mapping.get(name).contains("**")){
				String maxName = mapping.get(name);
				state gsState = gsStates.get(maxName);
				bin = gsState.getBin();
				ArrayList<String> gsClones = gsState.getClones();
				for(String gsClone: gsClones) {
					if(inverseMapping.containsKey(gsClone)) {
						clones.add(inverseMapping.get(gsClone));
					}
				}
			}
			state jsonState = new state(state.getName(), bin, clones, state.getTimeAdded(), id, url);
			states.put(state.getName(), jsonState);
		}
		
		ArrayList<pair> gsPairs = gsJson.getPairs();
		ArrayList<pair> pairs = new ArrayList<pair>();
		
		List<state> stateList = new ArrayList<>();
		stateList.addAll(states.values());
		stateList.sort((o1,o2) -> o1.getId()-o2.getId());
	
		for(state state1 : stateList) {
			int id1 = state1.getId();
			String name1 = state1.getName();
			String gsNameState1 = null;
			ArrayList<pair> state1pairs = null;
		
			if(mapping.containsKey(name1) && !mapping.get(name1).contains("**")){
				gsNameState1 = mapping.get(name1);
				String gsName = gsNameState1;
				state1pairs = gsPairs
								.stream()
								.filter(p -> p.getState1().equalsIgnoreCase(gsName)|| p.getState2().equalsIgnoreCase(gsName))
								.collect(Collectors.toCollection(ArrayList::new));
		
			}
			for(state state2:stateList) {
				int id2 = state2.getId();
				if(id2 <= id1) {
					continue;
				}

				String name2 = state2.getName();
				String gsNameState2 = null;
				ArrayList<pair> state1State2pairs = null;
				if(state1pairs !=null && (mapping.containsKey(name2) && !mapping.get(name2).contains("**"))) {
					gsNameState2 = mapping.get(name2);
					String gsName = gsNameState2;
					state1State2pairs = state1pairs
											.stream()
											.filter(p -> p.getState1().equalsIgnoreCase(gsName)|| p.getState2().equalsIgnoreCase(gsName))
											.collect(Collectors.toCollection(ArrayList::new));
				}
				
				
				int response = -1;
				int inferred = 0;
				ArrayList<String> tags = new ArrayList<>();
				String comments = null;
				
				if(gsNameState1!=null && gsNameState2!=null && gsNameState1.equalsIgnoreCase(gsNameState2)) {
					// This is a clone pair
					response = 0;
					inferred = 1;
				}
				else if(state1State2pairs !=null && !state1State2pairs.isEmpty()) {
					
					pair correspondingPair = state1State2pairs.get(0);
					if(correspondingPair.getResponse()==-1) {
						System.out.println(correspondingPair.getState1() + "" + correspondingPair.getState2());
					}
					response = correspondingPair.getResponse();
					inferred = 1;
					tags = correspondingPair.getTags();
					comments = correspondingPair.getComments();
				}
				else {
					System.out.println("Could not find corresponding pair");
				}
				
				pair newpair = new pair(name1, name2, response, inferred, tags, comments );
				pairs.add(newpair);
			}
			
		}
		
		ClassificationJson newJson = new ClassificationJson(states, pairs);
		return newJson;
		
	}
	
	
	
	private static void createClassificationJson() throws IOException {
		OutPutModel result = null;
		try {
			result = MAPPER.readValue(new File(Settings.resultJson), OutPutModel.class);
		} catch (JsonParseException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		} catch (JsonMappingException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		} catch (IOException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		}
		
		if(result == null) {
			System.out.println("Could not read the original Json result file for crawl");
			return;
		}
		
		
		getMapping();
		
		if(mapping==null) {
			System.out.println("Could not create mapping between GS and the crawl provided");
			return;
		}
		
		System.out.println(mapping);
		
		
		
		Gson gson = new GsonBuilder().setPrettyPrinting().create();
		System.out.println(result);
		ClassificationJson newJson = createNewObject( result);
		String outputString = gson.toJson(newJson);
		try {
			Path filePath = new File(Settings.outputDirApp + "classification.json").toPath();
			Path directory = filePath.getParent();
			if(!Files.exists(directory)) {
				Files.createDirectories(directory);
			}
			FileWriter writer = new FileWriter(Settings.outputDirApp + "GSmapping.txt");
			writer.write(mapping.toString());
			writer.flush();
			writer.close();
			
			writer = new FileWriter(filePath.toString());
			writer.write(outputString);
			writer.flush();
			writer.close();
		}catch(Exception ex) {
			//ex.printStackTrace();
			System.out.println("Could not create File");
		}
			
		
//		
//		gson.toJson(newJson, new FileWriter("newJson.json"));
	}
	
	
	private static void getMapping() {
		mapping =new HashMap<String, String>();
		inverseMapping = new HashMap<String, String>();
		Settings.RETAIN_ONLY_STATES = false;
		List<File> maxDomFiles = UtilsFileGetters.getDoms(Settings.maxCrawl_doms);
		Utils.sortStatesByNaturalOrdering(maxDomFiles);
		Settings.RETAIN_ONLY_STATES = true;
		List<File> newDomFiles = UtilsFileGetters.getDoms(Settings.pathToDoms);
		Utils.sortStatesByNaturalOrdering(newDomFiles);
		
		
		for(File domFile: newDomFiles) {
			try {
				String name = FilenameUtils.getBaseName(domFile.getName());
				String dom = FileUtils.readFileToString(domFile, Charset.defaultCharset());
////				dom = DomUtils.getDomWithoutHead(dom);
				for(File maxDomFile: maxDomFiles) {
					String maxName = FilenameUtils.getBaseName(maxDomFile.getName());
					String maxDom = FileUtils.readFileToString(maxDomFile, Charset.defaultCharset());
////					maxDom = DomUtils.getDomWithoutHead(maxDom);
					System.out.println(maxName);
					if(isEqual(dom,maxDom)) {
//						System.out.println(dom);
//						System.out.println(maxDom);
						mapping.put(name, maxName);
						inverseMapping.put(maxName, name);
						break;
					}
					
					System.out.println(name + " : " + maxName );
				}
				if(!mapping.containsKey(name)) {
					mapping.put(name, "**notFound**");
				}
			}
			catch(Exception ex) {
				ex.printStackTrace();
			}
		}
		
		
	}



	public static void main(String args[]) {
		if(args.length < 2) {
			System.out.println("USAGE: program <GoldStandardCrawl> <CrawlToAnalyze> <appName:optional>");
			System.exit(-1);
		}
		String maxCrawl = args[0];
		String crawlPath = args[1];
		if(args.length == 3) {
			if(args[2].trim().equalsIgnoreCase("petclinic"))
				currAppName = appName.petclinic;
			else if(args[2].trim().equalsIgnoreCase("addressbook"))
				currAppName = appName.addressbook;
			else if(args[2].trim().equalsIgnoreCase("claroline"))
				currAppName = appName.claroline;
			else if(args[2].trim().equalsIgnoreCase("dimeshift"))
				currAppName = appName.dimeshift;
			else if(args[2].trim().equalsIgnoreCase("mrbs"))
				currAppName = appName.mrbs;
			else if(args[2].trim().equalsIgnoreCase("pagekit"))
				currAppName = appName.pagekit;
			else if(args[2].trim().equalsIgnoreCase("phoenix"))
				currAppName = appName.phoenix;
			else if(args[2].trim().equalsIgnoreCase("ppma"))
				currAppName = appName.ppma;
			else if(args[2].trim().equalsIgnoreCase("mantisbt"))
				currAppName = appName.mantisbt;
			else {
				System.out.println("Unrecognized AppName." + args[2]+" Available apps <petclinic, addressbook, claroline, phonecat>");
				System.exit(-1);
			}
		}
		else {
			currAppName = appName.unknown;
		}
		String crawlFolderPath = "";

		if (crawlPath.endsWith(Settings.sep)) {
			Settings.outputDirApp = crawlPath + Settings.OUTPUT_DIRECTORY_NAME + Settings.sep;
			crawlFolderPath = crawlPath;
		} else {
			Settings.outputDirApp = crawlPath + Settings.sep + Settings.OUTPUT_DIRECTORY_NAME + Settings.sep;
			crawlFolderPath = crawlPath + Settings.sep;
		}
		
		if(maxCrawl.endsWith(Settings.sep)) {
			Settings.maxCrawl = maxCrawl;
			Settings.gsLocation = maxCrawl + "gs" + Settings.sep;
		}
		else {
			Settings.maxCrawl = maxCrawl + Settings.sep;
			Settings.gsLocation = maxCrawl + Settings.sep + "gs" + Settings.sep;
		}
		Settings.gsJson = Settings.gsLocation + "gsResults.json";
		Settings.maxCrawl_doms = Settings.maxCrawl + "doms" + Settings.sep;
		Settings.maxCrawl_screenshots = Settings.maxCrawl + "screenshots" + Settings.sep;
		Settings.maxCrawl_states = Settings.maxCrawl + "states" + Settings.sep;
		
		
		Settings.pathToDoms =  crawlFolderPath + "doms";
		Settings.pathToScreenshots = crawlFolderPath + "screenshots";
		Settings.pathToStates = crawlFolderPath + "states";
		Settings.resultJson = crawlFolderPath + "result.json";
		
		try {
			createClassificationJson();
		} catch (IOException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		}
	}



	
	
}

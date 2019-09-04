package comparators.dom;

import java.io.File;
import java.io.IOException;
import java.nio.charset.Charset;
import java.util.ArrayList;
import java.util.List;
import java.util.StringTokenizer;
import org.apache.commons.io.FileUtils;

import com.crawljax.stateabstractions.dom.simhash.BitHashRabin;
import com.crawljax.stateabstractions.dom.simhash.HammingDistance;
import com.crawljax.stateabstractions.dom.simhash.SimHash;
import utils.DomUtils;

import comparators.DomAbstractComparator;

public class SimHashComparator extends DomAbstractComparator {
	
	public static List<String> tokenizeContent(String content){
		List<String> tokens = new ArrayList<String>();
		StringTokenizer tokenizer = new StringTokenizer(content, ", ");
		if(tokenizer.hasMoreTokens())
			tokens.add(tokenizer.nextToken());
		return tokens;
	}

	public static String getSimHash(List<String> tokens) {
		SimHash simHashObj = new SimHash();
		for(String token: tokens) {
			BitHashRabin bHR = new BitHashRabin(token);
			simHashObj.add(bHR);
        }
        String simHash = simHashObj.getStringFingerprint().replaceAll(" ","");
        return simHash;
	}
	
	public static int calcuateSimHashDistance(String simHash1, String simHash2) {
		int distance = HammingDistance.hamming(simHash1, simHash2);
		return distance;
	}
	
	public static int calculateSimHashDistanceBetweenDoms(String dom1, String dom2) {
		List<String> tokens1 = tokenizeContent(DomUtils.getDOMContent(dom1));
		String simHash1 = getSimHash(tokens1);
		List<String> tokens2 = tokenizeContent(DomUtils.getDOMContent(dom2));
		String simHash2 = getSimHash(tokens2);
		calcuateSimHashDistance(simHash1, simHash2);
		return calcuateSimHashDistance(simHash1, simHash2);
	}
	
	private String name = "DOM-SIMHASH";
	private String page1;
	private String page2;

	public SimHashComparator() {
		super();
	}

	public SimHashComparator(String p1, String p2) {
		super();
		setPage1(p1);
		setPage2(p2);
	}

	@Override
	public double computeDistance() {

		String dom1 = null, dom2 = null;

		try {
			dom1 = FileUtils.readFileToString(new File(this.getPage1()), Charset.defaultCharset());
			dom2 = FileUtils.readFileToString(new File(this.getPage2()), Charset.defaultCharset());
		} catch (IOException e) {
			e.printStackTrace();
		}

		return calculateSimHashDistanceBetweenDoms(dom1, dom2);
	}

	@Override
	public String getName() {
		return this.name;
	}

	public String getPage1() {
		return this.page1;
	}

	public void setPage1(String p1) {
		this.page1 = p1;
	}

	public String getPage2() {
		return this.page2;
	}

	public void setPage2(String p2) {
		this.page2 = p2;
	}
}

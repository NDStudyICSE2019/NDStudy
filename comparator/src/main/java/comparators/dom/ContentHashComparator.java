package comparators.dom;

import java.io.File;
import java.io.IOException;
import java.nio.charset.Charset;

import org.apache.commons.io.FileUtils;

import com.idealista.tlsh.TLSH;
import com.idealista.tlsh.digests.Digest;
import com.idealista.tlsh.digests.DigestBuilder;

import comparators.DomAbstractComparator;

public class ContentHashComparator extends DomAbstractComparator {

	private String name = "DOM-contentHash";
	private String page1;
	private String page2;

	public ContentHashComparator() {
		super();
	}

	public ContentHashComparator(String p1, String p2) {
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

		int distance = 200;
		TLSH tlsh1, tlsh2;
		Digest digest1, digest2;

		try {
			tlsh1 = new TLSH(dom1);
			digest1 = new DigestBuilder().withHash(tlsh1.hash()).build();
		} catch (com.idealista.tlsh.exceptions.InsufficientComplexityException e) {
			//System.out.println("\n" + getPage1() + " does not have enough complexity. Returning max default value instead.");
			return distance;
		}

		try {
			tlsh2 = new TLSH(dom2);
			digest2 = new DigestBuilder().withHash(tlsh2.hash()).build();
		} catch (com.idealista.tlsh.exceptions.InsufficientComplexityException e) {
			//System.err.println("\n" + getPage2() + " does not have enough complexity. Returning max default value instead.");
			return distance;
		}

		distance = digest2.calculateDifference(digest1, true);

		return distance;
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

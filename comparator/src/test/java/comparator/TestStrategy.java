package comparator;

import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;
import org.opencv.core.Core;
import org.opencv.core.Mat;
import org.opencv.imgcodecs.Imgcodecs;

import comparators.dom.RTEDComparator;
import comparators.visual.PHashComparator;

class TestStrategy {

	@BeforeEach
	void setUp() throws Exception {
		System.loadLibrary(Core.NATIVE_LIBRARY_NAME);
	}

	@Test
	void testComparePHash() {
		PHashComparator averageHashComparator = new PHashComparator();
		Mat p1 = Imgcodecs.imread("src/test/resources/screenshots/state74.png");
		Mat p2 = Imgcodecs.imread("src/test/resources/screenshots/state77.png");

		averageHashComparator.setPage1(p1);
		averageHashComparator.setPage2(p2);
		double distance = averageHashComparator.computeDistance();
		System.out.println("PHash (state74, state77): " + distance);
	}
	
	@Test
	void testComparePHash2() {
		PHashComparator averageHashComparator = new PHashComparator();
		Mat p1 = Imgcodecs.imread("src/test/resources/screenshots/state63.png");
		Mat p2 = Imgcodecs.imread("src/test/resources/screenshots/state66.png");

		averageHashComparator.setPage1(p1);
		averageHashComparator.setPage2(p2);
		double distance = averageHashComparator.computeDistance();
		System.out.println("PHash (state63, state66): " + distance);
	}

	@Test
	//@Ignore
	void testCompareRTEDClaroline() {
		RTEDComparator rtedComparator = new RTEDComparator();

		rtedComparator.setPage1("src/test/resources/doms/state25.html");
		rtedComparator.setPage2("src/test/resources/doms/state320.html");

		double distance = rtedComparator.computeDistance();
		System.out.println("RTED (state25, state320): " + distance);
	}
	
	@Test
	//@Ignore
	void testCompareRTEDAddressBook() {
		RTEDComparator rtedComparator = new RTEDComparator();

		rtedComparator.setPage1("src/test/resources/doms/state74.html");
		rtedComparator.setPage2("src/test/resources/doms/state77.html");

		double distance = rtedComparator.computeDistance();
		System.out.println("RTED (state74, state77): " + distance);
	}
	
	@Test
	void testCompareRTEDAddressBook2() {
		RTEDComparator rtedComparator = new RTEDComparator();

		rtedComparator.setPage1("src/test/resources/doms/state63.html");
		rtedComparator.setPage2("src/test/resources/doms/state66.html");

		double distance = rtedComparator.computeDistance();
		System.out.println("RTED (state63, state66): " + distance);
	}

}

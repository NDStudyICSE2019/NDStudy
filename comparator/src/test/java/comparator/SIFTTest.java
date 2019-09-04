package comparator;

import org.opencv.core.Core;
import org.opencv.core.Mat;
import org.opencv.imgcodecs.Imgcodecs;

import comparators.visual.SIFTComparator;

public class SIFTTest {
	
	static {
        System.loadLibrary(Core.NATIVE_LIBRARY_NAME);
    }
	
	public static void main(String args[]) {
		SIFTComparator siftComparator = new SIFTComparator();
		Mat p1 = Imgcodecs.imread("src/main/resources/petclinic-toy/screenshots/state10.png", Imgcodecs.IMREAD_GRAYSCALE);
		Mat p2 = Imgcodecs.imread("src/main/resources/petclinic-toy/screenshots/index.png", Imgcodecs.IMREAD_GRAYSCALE);

		siftComparator.setPage1(p1);
		siftComparator.setPage2(p2);
		System.out.println("Similarity score (index, state10): " + siftComparator.computeDistance());
	
		p1 = Imgcodecs.imread("src/main/resources/petclinic-toy/screenshots/state5.png", Imgcodecs.IMREAD_GRAYSCALE);
		p2 = Imgcodecs.imread("src/main/resources/petclinic-toy/screenshots/index.png", Imgcodecs.IMREAD_GRAYSCALE);

		siftComparator.setPage1(p1);
		siftComparator.setPage2(p2);
		System.out.println("Similarity score (index, state5): " + siftComparator.computeDistance());
	
		p1 = Imgcodecs.imread("src/main/resources/petclinic-toy/screenshots/index.png", Imgcodecs.IMREAD_GRAYSCALE);
		p2 = Imgcodecs.imread("src/main/resources/petclinic-toy/screenshots/index.png", Imgcodecs.IMREAD_GRAYSCALE);

		siftComparator.setPage1(p1);
		siftComparator.setPage2(p2);
		System.out.println("Similarity score (index, index): " + siftComparator.computeDistance());
	}
}

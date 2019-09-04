package comparators.visual;

import java.awt.Point;
import java.awt.image.BufferedImage;
import java.io.File;
import java.io.IOException;
import java.util.ArrayList;
import java.util.List;
import java.util.concurrent.ForkJoinPool;

import javax.imageio.ImageIO;

import org.opencv.core.Mat;

import comparators.VisualAbstractComparator;
import utils.PerceptualImageDifferencing;
import utils.Utils;

public class PDiffComparator extends VisualAbstractComparator {

	private String name = "VISUAL-PDiff";
	private String page1;
	private String page2;

	/* getting correct perceptible differences only. */
	private double COLOR_FACTOR = 0.0;
	private double FOV = 27;
	private double GAMMA = 4.2;
	private double LUMINANCE = 20.0;
	private boolean LUMINANCE_ONLY = false;
	private int THRESHOLD_PIXELS = 100;
	private int PERCENTAGE_OF_TOTAL_IMAGE_SIZE = 0;

	private String differenceColor = Utils.getHexFromDecimal(PerceptualImageDifferencing.COLOR_FAIL);

	private static final ForkJoinPool pool = new ForkJoinPool();

	public PDiffComparator() {
		super();
	}

	public PDiffComparator(String p1, String p2) {
		super();
		setPage1(p1);
		setPage2(p2);
	}

	@Override
	public double computeDistance() {

		List<Point> differencePixels = new ArrayList<Point>();

		BufferedImage imgA = null, imgB = null;
		try {
			imgA = ImageIO.read(new File(page1));
			imgB = ImageIO.read(new File(page2));
		} catch (IOException e) {
			e.printStackTrace();
		}

		int width = Math.max(imgA.getWidth(), imgB.getWidth());
		int height = Math.max(imgA.getHeight(), imgB.getHeight());

		THRESHOLD_PIXELS = (int) (width * height * ((double) PERCENTAGE_OF_TOTAL_IMAGE_SIZE / 100));

		// BufferedImage imgDiff = (differenceImageFullPath != null) ? new
		// BufferedImage(width, height, BufferedImage.TYPE_INT_ARGB) : null;
		BufferedImage imgDiff = new BufferedImage(width, height, BufferedImage.TYPE_INT_ARGB);

		PerceptualImageDifferencing.Builder builder = new PerceptualImageDifferencing.Builder();
		builder.setColorFactor(COLOR_FACTOR);
		builder.setFieldOfView(FOV);
		builder.setGamma(GAMMA);
		builder.setLuminance(LUMINANCE);
		builder.setLuminanceOnly(LUMINANCE_ONLY);
		builder.setThresholdPixels(THRESHOLD_PIXELS);

		PerceptualImageDifferencing pd = builder.build();
		pd.compare(pool, imgA, imgB, imgDiff);
		// pd.dump();

		for (int r = 0; r < imgDiff.getWidth(); r++) {
			for (int c = 0; c < imgDiff.getHeight(); c++) {
				if (Utils.getHexFromDecimal(imgDiff.getRGB(r, c)).equalsIgnoreCase(differenceColor)) {
					differencePixels.add(new Point(r, c));
				}
			}
		}

		// System.out.println("Difference pixels = " + differencePixels.size());
		double differentPixels = (double) differencePixels.size();
		differentPixels /= (width * height);
		return differentPixels;

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

	@Override
	public void setPage1(Mat imread) {
		throw new IllegalArgumentException("Method setPage does not allow parameters of type Mat");
	}

	@Override
	public void setPage2(Mat imread) {
		throw new IllegalArgumentException("Method setPage does not allow parameters of type Mat");
	}

}

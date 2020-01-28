For getting distance between states in a crawl model (has to be produced by crawljax latest version):

    build the comparator project
    set the location of the crawl to use in  comparator/runBatch_GSComp.sh
    run the shell script : ./comparator/runBatch_GSComp.sh


To create a ground truth Model 

    set the location of crawl you want to use as ground truth in (htmlCreator.py) and run :
    python htmlCreator.py

    After labelling the pairs by open html file in crawl_folder/gs/gs.html, save the gsResults.json in the same folder.

    Now this ground truth can be used to analyze other crawls that are created for this subject
 


Thresholds for each subject have been computed and saved in 

    threshold_data.py


For running crawls :
    set the location of crawljax/examples/target/example_xxx_jar_with_dependencies.jar in 
        comparator/pythonCode/runCrawljaxBatch.py (BASE_COMMAND)
 
    python comparator/runCrawljaxBatch.py

        for 5 minute crawls: runAllAlgos()
        for 30 minute crawls: runBestCrawls()
 
 

 For RQ2 run : 
    python3 comparator/pythonCode/RQ1.py
 
 For RQ3:
    python comparator/analyzeCrawl.py
    {
        for 5 minute crawls: analyzeAllCrawls()
        for 30 minute crawls: analyzeBestCrawls()
     }
     
     - Needs to be twice to generate classification.html for a crawl to be analyzed.
     - save the classification json in "saveJsons" folder by opening classification.html.
     - run analyzeCrawl.py again to obtain the statistics for the crawl.

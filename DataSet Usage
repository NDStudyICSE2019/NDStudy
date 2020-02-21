DS.db (contains 493K state-pairs and 1000 labelled pairs) 
SS.db (contains 97.5K labelled state-pairs for 9 subjects)
TS.db (same as DS but 500 labelled pairs (disjoint from 1000 in DS) for validation 

  DS.db:
        Contains all state-pairs from 1031 web apps randomly selected from alexa top sites
        
        contains 3 tables 
        apps, nearduplicates, states
        
        apps contains names and characteristics for the all 1031 crawls  
        nearduplicates contains the distance values for each of the 493K state-pairs spread across all 1031 crawls 
        1000 of the rows in nearduplicates have manual labelling (HUMAN_CLASSIFICATION) 0->clone, 1->nearduplicate, 2->different
        states contains the characteristics for each state in all the crawls.
        
  SS.db:
         contains 3 tables 
         apps, nearduplicates, states
         
         apps contains names and characteristics for ground truth crawls of the nine subject apps used in the study
         nearduplicates contains the distance values and manual classification for each of the 97.5K state-pairs in ground truth models 
         states contains the characteristics for each state in all ground truth models.
         
  TS.db
        Same as DS.db
        contains 500 pairs instead of 1000
        These 500 used for validation (explained in the paper)

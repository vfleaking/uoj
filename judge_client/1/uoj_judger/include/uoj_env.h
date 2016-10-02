#include "uoj_work_path.h"

#define UOJ_DATA_PATH UOJ_WORK_PATH "/data"
#define UOJ_RESULT_PATH UOJ_WORK_PATH "/result"

#define RS_SPJ_BASE 1000
#define failed_spj RS_SPJ_BASE
#define successed_hack 100000
#define RS_SPJ RS_SPJ_BASE
#define RS_HACK successed_hack
#define RS_AC 0
#define RS_WA 1
#define RS_RE 2
#define RS_MLE 3
#define RS_TLE 4
#define RS_OLE 5
#define RS_DGS 6
#define RS_JGF 7
#define RS_SPJ_AC (RS_SPJ + RS_AC)
#define RS_SPJ_RE (RS_SPJ_BASE + RS_RE)
#define RS_SPJ_MLE (RS_SPJ_BASE + RS_MLE)
#define RS_SPJ_TLE (RS_SPJ_BASE + RS_TLE)
#define RS_SPJ_OLE (RS_SPJ_BASE + RS_OLE)
#define RS_SPJ_DGS (RS_SPJ_BASE + RS_DGS)
#define RS_SPJ_JGF (RS_SPJ_BASE + RS_JGF)
#define RS_HK_RE (successed_hack + RS_RE)
#define RS_HK_MLE (successed_hack + RS_MLE)
#define RS_HK_TLE (successed_hack + RS_TLE)
#define RS_HK_OLE (successed_hack + RS_OLE)
#define RS_HK_DGS (successed_hack + RS_DGS)
#define RS_HK_JGF (successed_hack + RS_JGF)
#define RS_HK_SPJ_RE (successed_hack + RS_SPJ_RE)
#define RS_HK_SPJ_MLE (successed_hack + RS_SPJ_MLE)
#define RS_HK_SPJ_TLE (successed_hack + RS_SPJ_TLE)
#define RS_HK_SPJ_OLE (successed_hack + RS_SPJ_OLE)
#define RS_HK_SPJ_DGS (successed_hack + RS_SPJ_DGS)
#define RS_HK_SPJ_JGF (successed_hack + RS_SPJ_JGF)


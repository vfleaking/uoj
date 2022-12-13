#include "catch_amalgamated.hpp"
// uncomment the following to show every command being executed
// #define UOJ_SHOW_EVERY_CMD
#include "uoj_judger.h"
#include <random>
#include <filesystem>
#include <thread>
#include <vector>

namespace fs = std::filesystem;

static fs::path init_random_tmp_dir(const string &src) {
    using namespace std;

    auto tmp_dir = fs::temp_directory_path();
    random_device dev;
    mt19937 prng(dev());
    uniform_int_distribution<uint64_t> rand;

    fs::create_directory(tmp_dir / "judger_test");

    while (true) {
        stringstream ss;
        ss << src << "_" << hex << rand(prng);
        fs::path path = tmp_dir / "judger_test" / ss.str();
        if (fs::create_directory(path)) {
            main_path = fs::read_symlink("/proc/self/exe").parent_path().parent_path();
            work_path = path / "work";
            fs::copy(fs::path(main_path) / "tests" / src, work_path, fs::copy_options::recursive);
            fs::current_path(work_path);
            result_path = path / "result";
            fs::create_directory(result_path);
            data_path = fs::path(main_path) / "tests" / "data";
            runp::run_path = fs::path(main_path) / "run";
            return path;
        }
    }
}

static runp::config get_simple_rpc(const string &name) {
    runp::config rpc(name);
    rpc.result_file_name = result_path + "/run_result.txt";
    rpc.input_file_name = "/dev/null";
    rpc.output_file_name = work_path + "/out";
    rpc.limits.time = 1;
    rpc.limits.memory = 128;
    rpc.limits.output = 5;
    rpc.limits.real_time = 3;
    rpc.limits.stack = 128;
    return rpc;
}

static string prepare_file(const fs::path &path) {
    if (fs::exists(path)) {
        return path;
    } else if (fs::exists(path.string() + ".zip")) {
        execute("unzip", "-q", path.string() + ".zip");
        return path;
    } else {
        return "/dev/null";
    }
}

TEST_CASE("check result type of run_program", "[run_program]") {
    pair<string, runp::RS_TYPE> li[] = {
        {"hellocpp"     , runp::RS_AC },
        {"killme"       , runp::RS_RE },
        {"div0"         , runp::RS_RE },
        {"glibcerr"     , runp::RS_RE },
        {"newer"        , runp::RS_MLE},
        {"oler"         , runp::RS_OLE},
        {"fork"         , runp::RS_DGS},
    };

    for (auto &ts : li) {
        SECTION(ts.first) {
            fs::path path = init_random_tmp_dir(ts.first);
            REQUIRE(compile("answer").succeeded);
            runp::result res = run_program(get_simple_rpc("answer"));
            if (res.type == runp::RS_AC && res.exit_code != 0) {
                res.type = runp::RS_RE;
            }
            REQUIRE(res.type == ts.second);
            fs::remove_all(path);
        }
    }
}

TEST_CASE("TLE test", "[run_program]") {
    pair<string, runp::RS_TYPE> li[] = {
        {"sleeper"      , runp::RS_TLE},
        {"forkret"      , runp::RS_AC }, // should it be TLE? Or RE?
        {"fforkret"     , runp::RS_AC }, // should it be TLE? Or RE?
        {"forkwait"     , runp::RS_TLE},
        {"thdetach"     , runp::RS_TLE},
        {"thexec"       , runp::RS_TLE},
        {"thexit"       , runp::RS_TLE},
        {"thret"        , runp::RS_TLE},
    };

    for (auto &ts : li) {
        SECTION(ts.first) {
            fs::path path = init_random_tmp_dir(ts.first);
            REQUIRE(execute("g++", "-o", "answer", "answer14.cpp", "-pthread") == 0);
            runp::config rpc = get_simple_rpc("answer");
            rpc.unsafe = true;
            runp::result res = run_program(rpc);
            REQUIRE(res.type == ts.second);
            fs::remove_all(path);
        }
    }
}

TEST_CASE("double TLE test", "[run_program]") {
    tuple<string, double, double, runp::RS_TYPE> li[] = {
        {"busy6.666" , 6.676, 10, runp::RS_AC },
        {"busy6.666" , 6.656, 10, runp::RS_TLE},
        {"busy6.666" , 10,   6.8, runp::RS_AC },
        {"busy6.666" , 10,   6.3, runp::RS_TLE},
    };

    for (auto &ts : li) {
        auto [ name, tl, rtl, rst ] = ts;
        SECTION(name + "-" + to_string(tl) + "-" + to_string(rtl)) {
            fs::path path = init_random_tmp_dir(name);
            REQUIRE(execute("g++", "-o", "answer", "answer14.cpp", "-pthread") == 0);
            runp::config rpc = get_simple_rpc("answer");
            rpc.unsafe = true;
            rpc.limits.time = tl;
            rpc.limits.real_time = rtl;
            runp::result res = run_program(rpc);
            REQUIRE(res.type == rst);
            fs::remove_all(path);
        }
    }
}


TEST_CASE("java test", "[run_program]") {
    pair<string, runp::RS_TYPE> li[] = {
        {"plus"           , runp::RS_AC },
        {"one-more-thread", runp::RS_DGS},
        {"482629"         , runp::RS_AC },
        {"482722"         , runp::RS_AC },
    };

    for (auto &ts : li) {
        for (string ver : {"7", "8", "11", "14", "17"}) {
            SECTION(ts.first + "-java" + ver) {
                fs::path path = init_random_tmp_dir(ts.first + "-java");
                config["answer_language"] = "Java" + ver;
                REQUIRE(compile("answer").succeeded);
                runp::config rpc = get_simple_rpc("answer");
                rpc.input_file_name = prepare_file("./input.txt");
                rpc.type = runp::get_type_from_lang("Java" + ver);
                runp::result res = run_program(rpc);
                if (res.type == runp::RS_AC && res.exit_code != 0) {
                    res.type = runp::RS_RE;
                }
                REQUIRE(res.type == ts.second);
                fs::remove_all(path);
            }
        }
    }
}

TEST_CASE("multijava", "[run_program]") {
    pair<string, runp::RS_TYPE> li[] = {
        {"482629"         , runp::RS_AC },
        {"482722"         , runp::RS_AC },
    };

    for (auto &ts : li) {
        for (string ver : {/* "8", */ "11", "17"}) {
            SECTION(ts.first + "-java" + ver) {
                fs::path path = init_random_tmp_dir(ts.first + "-java");

                prepare_file("./input.txt");
                auto mycompile = [&](int i) {
                    return [i, ts, ver] {
                        file_copy(work_path + "/answer.code", work_path + "/answer" + to_string(i) + ".code");
                        config["answer" + to_string(i) + "_language"] = "Java" + ver;
                        REQUIRE(compile("answer" + to_string(i)).succeeded);
                        runp::config rpc = get_simple_rpc("answer" + to_string(i));
                        rpc.input_file_name = prepare_file("./input.txt");
                        rpc.type = runp::get_type_from_lang("Java" + ver);
                        runp::result res = run_program(rpc);
                        if (res.type == runp::RS_AC && res.exit_code != 0) {
                            res.type = runp::RS_RE;
                        }
                        REQUIRE(res.type == ts.second);
                    };
                };

                std::vector<std::thread> threads;
                for (int k = 0; k < 4; k++) {
                    threads.emplace_back(mycompile(k));
                }
                for (int k = 0; k < 4; k++) {
                    threads[k].join();
                }
                fs::remove_all(path);
            }
        }
    }
}

TEST_CASE("coin2 benchmark", "[interaction][benchmark]") {
    fs::path path = init_random_tmp_dir("coin2");

    REQUIRE(execute("python3", "gen.py", ">coin.in") == 0);
    REQUIRE(execute("g++", "-o", "interactor", "interactor14.cpp", "-I" + main_path + "/include", "-w") == 0);
    REQUIRE(compile("std").succeeded);

    prepare_interactor(true);

    runp::limits_t lim = RL_DEFAULT;
    runp::limits_t ilim = RL_INTERACTOR_DEFAULT;
    ilim.time = 2;

    BENCHMARK("coin2 interaction") {
        auto res = run_simple_interaction(
            "coin.in", "coin.out",
            "coin.real.in", "coin.real.out",
            lim, ilim,
            "std"
        );
        REQUIRE(res.res.type == runp::RS_AC);
        REQUIRE(res.ires.type == runp::RS_AC);
    };

    execute("mkfifo", "fifo");
    BENCHMARK("coin2 raw interaction") {
        execute("./interactor", "coin.in", "/dev/stdin", "coin.out", "<fifo", "2>/dev/null", "|", "./std", ">fifo", "2>/dev/null");
    };

    fs::remove_all(path);
}

TEST_CASE("coin2 test", "[interaction]") {
    tuple<string, runp::RS_TYPE, runp::RS_TYPE, int> li[] = {
        {"giveup"      , runp::RS_AC , runp::RS_AC, 0},
        {"wrongfmt"    , runp::RS_AC , runp::RS_AC, 0},
        {"cinfirst"    , runp::RS_TLE, runp::RS_AC, 0},
    };

    for (auto &ts : li) {
        auto [ name, rst, irst, scr ] = ts;
        SECTION(name) {
            fs::path path = init_random_tmp_dir("coin2");

            REQUIRE(execute("python3", "gen.py", ">coin.in") == 0);
            REQUIRE(execute("g++", "-o", "interactor", "interactor14.cpp", "-I" + main_path + "/include", "-w") == 0);
            REQUIRE(compile(name).succeeded);
            prepare_interactor(true);

            runp::limits_t lim = RL_DEFAULT;
            runp::limits_t ilim = RL_INTERACTOR_DEFAULT;
            ilim.time = 2;

            auto res = run_simple_interaction(
                "coin.in", "coin.out",
                "coin.real.in", "coin.real.out",
                lim, ilim,
                name
            );
            REQUIRE(res.res.type == rst);
            REQUIRE(res.ires.type == irst);
            REQUIRE(res.ires.scr == scr);

            fs::remove_all(path);
        }
    }
}

TEST_CASE("double to time structure test", "[time limit]") {
    for (int t = 0; t < 10000; t++) {
        timeval val = runp::double_to_timeval(t / 1000.);
        REQUIRE((val.tv_sec == t / 1000 && val.tv_usec == (t % 1000) * 1000));
        timespec spec = runp::double_to_timespec(t / 1000.);
        REQUIRE((spec.tv_sec == t / 1000 && spec.tv_nsec == (t % 1000) * 1'000'000));
    }

    for (int t = 25000; t < 30000; t++) {
        timeval val = runp::double_to_timeval(t / 1000.);
        REQUIRE((val.tv_sec == t / 1000 && val.tv_usec == (t % 1000) * 1000));
        timespec spec = runp::double_to_timespec(t / 1000.);
        REQUIRE((spec.tv_sec == t / 1000 && spec.tv_nsec == (t % 1000) * 1'000'000));
    }

    for (int t = 900000; t < 902000; t++) {
        timeval val = runp::double_to_timeval(t / 1000.);
        REQUIRE((val.tv_sec == t / 1000 && val.tv_usec == (t % 1000) * 1000));
        timespec spec = runp::double_to_timespec(t / 1000.);
        REQUIRE((spec.tv_sec == t / 1000 && spec.tv_nsec == (t % 1000) * 1'000'000));
    }

    for (int t = 970000; t < 1000000; t++) {
        timeval val = runp::double_to_timeval(t / 1000.);
        REQUIRE((val.tv_sec == t / 1000 && val.tv_usec == (t % 1000) * 1000));
        timespec spec = runp::double_to_timespec(t / 1000.);
        REQUIRE((spec.tv_sec == t / 1000 && spec.tv_nsec == (t % 1000) * 1'000'000));
    }
}

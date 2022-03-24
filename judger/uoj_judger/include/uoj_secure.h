#include <iostream>
#include <sstream>
#include <string>
#include <cstring>

#ifdef _MSC_VER
#   define UOJ_NORETURN __declspec(noreturn)
#elif defined __GNUC__
#   define UOJ_NORETURN __attribute__ ((noreturn))
#else
#   define UOJ_NORETURN
#endif

namespace {
	typedef unsigned char u8;
	typedef unsigned u32;
	typedef unsigned long long u64;

	using namespace std;

	struct sha256_t {
		u8 sum[32];

		string to_str() {
			return string((char*)sum, 32);
		}
	};

	inline u32 uoj_sha2_rotr(u32 x, int n) {
		return x >> n | x << (32 - n);
	}

	void uoj_sha256_chunk(u8 *chunk, u32 *hs) {
		static const u32 k[] = {
			0x428a2f98, 0x71374491, 0xb5c0fbcf, 0xe9b5dba5, 0x3956c25b, 0x59f111f1, 0x923f82a4, 0xab1c5ed5,
			0xd807aa98, 0x12835b01, 0x243185be, 0x550c7dc3, 0x72be5d74, 0x80deb1fe, 0x9bdc06a7, 0xc19bf174,
			0xe49b69c1, 0xefbe4786, 0x0fc19dc6, 0x240ca1cc, 0x2de92c6f, 0x4a7484aa, 0x5cb0a9dc, 0x76f988da,
			0x983e5152, 0xa831c66d, 0xb00327c8, 0xbf597fc7, 0xc6e00bf3, 0xd5a79147, 0x06ca6351, 0x14292967,
			0x27b70a85, 0x2e1b2138, 0x4d2c6dfc, 0x53380d13, 0x650a7354, 0x766a0abb, 0x81c2c92e, 0x92722c85,
			0xa2bfe8a1, 0xa81a664b, 0xc24b8b70, 0xc76c51a3, 0xd192e819, 0xd6990624, 0xf40e3585, 0x106aa070,
			0x19a4c116, 0x1e376c08, 0x2748774c, 0x34b0bcb5, 0x391c0cb3, 0x4ed8aa4a, 0x5b9cca4f, 0x682e6ff3,
			0x748f82ee, 0x78a5636f, 0x84c87814, 0x8cc70208, 0x90befffa, 0xa4506ceb, 0xbef9a3f7, 0xc67178f2
		};

		u32 w[64];
		for (int i = 0; i < 16; i++) {
			w[i] =   ((u32) chunk[i << 2 | 3])
				   | ((u32) chunk[i << 2 | 2] <<  8)
				   | ((u32) chunk[i << 2 | 1] << 16)
				   | ((u32) chunk[i << 2    ] << 24);
		}

		for (int i = 16; i < 64; i++) {
			u32 s0 = uoj_sha2_rotr(w[i - 15], 7) ^ uoj_sha2_rotr(w[i - 15], 18) ^ (w[i - 15] >> 3);
			u32 s1 = uoj_sha2_rotr(w[i - 2], 17) ^ uoj_sha2_rotr(w[i - 2], 19) ^ (w[i - 2] >> 10);
			w[i] = w[i - 16] + s0 + w[i - 7] + s1;
		}

		u32 a = hs[0], b = hs[1], c = hs[2], d = hs[3], e = hs[4], f = hs[5], g = hs[6], h = hs[7];

		for (int i = 0; i < 64; i++) {
			u32 s1 = uoj_sha2_rotr(e, 6) ^ uoj_sha2_rotr(e, 11) ^ uoj_sha2_rotr(e, 25);
			u32 ch = (e & f) ^ (~e & g);
			u32 temp1 = h + s1 + ch + k[i] + w[i];
			u32 s0 = uoj_sha2_rotr(a, 2) ^ uoj_sha2_rotr(a, 13) ^ uoj_sha2_rotr(a, 22);
			u32 maj = (a & b) ^ (a & c) ^ (b & c);
			u32 temp2 = s0 + maj;
			h = g;
			g = f;
			f = e;
			e = d + temp1;
			d = c;
			c = b;
			b = a;
			a = temp1 + temp2;
		}

		hs[0] += a, hs[1] += b, hs[2] += c, hs[3] += d, hs[4] += e, hs[5] += f, hs[6] += g, hs[7] += h;
	}

	sha256_t uoj_sha256(int n, u8 *m) {
		u32 hs[] = {0x6a09e667, 0xbb67ae85, 0x3c6ef372, 0xa54ff53a, 0x510e527f, 0x9b05688c, 0x1f83d9ab, 0x5be0cd19};

		u64 len = n * 8;

		int r_n = 0;
		u8 r[128];
		for (int i = 0; i < n; i += 64) {
			if (i + 64 <= n) {
				uoj_sha256_chunk(m + i, hs);
			} else {
				for (int j = i; j < n; j++) {
					r[r_n++] = m[j];
				}
			}
		}

		r[r_n++] = 0x80;
		while ((r_n + 8) % 64 != 0) {
			r[r_n++] = 0;
		}
		for (int i = 1; i <= 8; i++) {
			r[r_n++] = len >> (64 - i * 8);
		}

		for (int i = 0; i < r_n; i += 64) {
			uoj_sha256_chunk(r + i, hs);
		}

		sha256_t sum;
		for (int i = 0; i < 8; i++) {
			for (int j = 0; j < 4; j++) {
				sum.sum[i << 2 | j] = hs[i] >> (32 - (j + 1) * 8);
			}
		}
		return sum;
	}
	sha256_t uoj_sha256(const string &m) {
		return uoj_sha256((int)m.length(), (u8*)m.data());
	}

	sha256_t uoj_hmac(const string &k, const string &m) {
		string ki = k, ko = k;
		for (int i = 0; i < (int)k.length(); i++) {
			ki[i] ^= 0x36;
			ko[i] ^= 0x5c;
		}
		return uoj_sha256(ko + uoj_sha256(ki + m).to_str());
	}

	class uoj_mt_rand_engine {
		static const int N = 312;
		static const int M = 156;
		static const int R = 31;
		static const u64 LM = (1llu << R) - 1;
		static const u64 UM = ~LM;
		static const u64 F = 6364136223846793005llu;

		u64 mt[N];
		int index;

		void init(u64 seed) {
			index = N;
			mt[0] = seed;
			for (int i = 1; i < N; i++) {
				mt[i] = F * (mt[i - 1] ^ (mt[i - 1] >> 62)) + i;
			}
		}

		void twist() {
			for (int i = 0; i < N; i++) {
				u64 x = (mt[i] & UM) + (mt[(i + 1) % N] & LM);
				u64 xA = x >> 1;
				if (x & 1) {
					xA ^= 0xb5026f5aa96619e9llu;
				}
				mt[i] = mt[(i + M) % N] ^ xA;
			}
			index = 0;
		}

	public:
		uoj_mt_rand_engine(u64 seed) {
			init(seed);
		}
		uoj_mt_rand_engine(const string &s) {
			sha256_t sum = uoj_sha256(s);

			u64 seed = 0;
			for (int i = 0; i < 8; i++)
				seed = seed << 8 | sum.sum[i];

			init(seed);
		}

		u64 next() {
			if (index >= N) {
				twist();
			}

			u64 y = mt[index];
			y ^= (y >> 29) & 0x5555555555555555llu;
			y ^= (y << 17) & 0x71d67fffeda60000llu;
			y ^= (y << 37) & 0xfff7eee000000000llu;
			y ^= y >> 43;

			index++;

			return y;
		}

		string randstr(int n, string charset="0123456789abcdefghijklmnopqrstuvwxyz") {
			string s;
			for (int i = 0; i < n; i++) {
				s += charset[next() % charset.length()];
			}
			return s;
		}
	};

	class uoj_cipher {
		string key;
	
	public:
		uoj_cipher() {}
		uoj_cipher(const string &_key) : key(_key) {}

		void set_key(const string &_key) {
			key = _key;
		}

		void encrypt(string &m) {
			uoj_mt_rand_engine rnd(key);

			string hmac = uoj_hmac(key, m).to_str();

			m.push_back(0x80);
			while ((m.length() + 32) % 512 != 0) {
				m.push_back(0x00);
			}

			m += hmac;
			for (int i = 0; i < (int)m.length(); i += 8) {
				u64 r = rnd.next();
				for (int j = i; j < i + 4; j++) {
					m[j] = (u8)m[j] ^ (u8)r;
					r >>= 16;
				}
			}
		}
		bool decrypt(string &m) {
			uoj_mt_rand_engine rnd(key);

			if (m.empty() || m.length() % 512 != 0) {
				return false;
			}
			for (int i = 0; i < (int)m.length(); i += 8) {
				u64 r = rnd.next();
				for (int j = i; j < i + 4; j++) {
					m[j] = (u8)m[j] ^ (u8)r;
					r >>= 16;
				}
			}
			string hmac = m.substr(m.length() - 32);
			int len = m.length() - 33;
			while (len >= 0 && (u8)m[len] != 0x80) {
				len--;
			}
			if (len < 0) {
				return false;
			}
			m.resize(len);
			if (uoj_hmac(key, m).to_str() != hmac) {
				return false;
			}
			return true;
		}
	};


	class uoj_secure_io {
		FILE fake_f, true_outf;
		string input_m;

		string key;
		uoj_cipher cipher;

	public:
		istringstream in;
		ostringstream out;

		uoj_secure_io() {
			srand(time(NULL));

			const int BUFFER_SIZE = 1024;
			u8 buffer[BUFFER_SIZE + 1];
			while (!feof(stdin)) {
				int ret = fread(buffer, 1, BUFFER_SIZE, stdin);
				if (ret < 0) {
					break;
				}
				input_m.append((char *)buffer, ret);
			}
			fclose(stdin);

			for (int i = 0; i < (int)sizeof(fake_f); i++)
				((u8*)&fake_f)[i] = rand();

			memcpy(&true_outf, stdout, sizeof(FILE));
			memcpy(stdout, &fake_f, sizeof(FILE));
		}

		void init_with_key(const string &_key) {
			cerr.tie(NULL);

			key = _key;
			cipher.set_key(key);

			if (!cipher.decrypt(input_m)) {
				end("Unauthorized input");
			}

			in.str(input_m);
		}

		string input() {
			return input_m;
		}

		UOJ_NORETURN void end(string m) {
			memcpy(stdout, &true_outf, sizeof(FILE));

			if (!out.str().empty()) {
				if (m.empty()) {
					m = out.str();
				} else {
					m = out.str() + m;
				}
			}

			cipher.encrypt(m);
			fwrite(m.data(), 1, m.length(), stdout);

			fclose(stdout);
			exit(0);
		}

		UOJ_NORETURN void end() {
			end("");
		}
	};
}

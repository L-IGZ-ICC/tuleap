# This file has been generated by node2nix 1.9.0. Do not edit!

{nodeEnv, fetchurl, fetchgit, nix-gitignore, stdenv, lib, globalBuildInputs ? []}:

let
  sources = {};
in
{
  "npm-^6" = nodeEnv.buildNodePackage {
    name = "npm";
    packageName = "npm";
    version = "6.14.11";
    src = fetchurl {
      url = "https://registry.npmjs.org/npm/-/npm-6.14.11.tgz";
      sha512 = "1Zh7LjuIoEhIyjkBflSSGzfjuPQwDlghNloppjruOH5bmj9midT9qcNT0tRUZRR04shU9ekrxNy9+UTBrqeBpQ==";
    };
    buildInputs = globalBuildInputs;
    meta = {
      description = "a package manager for JavaScript";
      homepage = "https://docs.npmjs.com/";
      license = "Artistic-2.0";
    };
    production = true;
    bypassCache = true;
    reconstructLock = true;
  };
}

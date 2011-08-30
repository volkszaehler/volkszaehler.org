#                                               -*- Autoconf -*-
# Process this file with autoconf to produce a configure script.

AC_PREREQ([2.67])
AC_INIT([vzlogger], [0.2], [http://bugs.volkszaehler.org])
AM_INIT_AUTOMAKE(vzlogger, 0.2)
AC_CONFIG_SRCDIR([src/main.c])
AC_CONFIG_HEADERS([config.h])
AC_CONFIG_FILES([Makefile src/Makefile docs/Makefile])

# Checks for programs.
AC_PROG_CC

# Checks for libraries.
PKG_CHECK_MODULES([DEPS], [json >= 0.9 libcurl >= 7.21.0])

# Checks for header files.
AC_CHECK_HEADERS([fcntl.h stddef.h stdint.h stdlib.h string.h sys/time.h termios.h unistd.h])

# Checks for typedefs, structures, and compiler characteristics.
AC_TYPE_MODE_T
AC_TYPE_SIZE_T

# Checks for library functions.
AC_FUNC_MALLOC
AC_FUNC_REALLOC
AC_FUNC_STRERROR_R
AC_CHECK_FUNCS([gettimeofday memset sqrt strchr strtol])

# local interface support
AC_ARG_ENABLE(
    [local-interface], 
    [AS_HELP_STRING([--enable-local], [enable support for local HTTPd (def=no)])],
    [local=true],
    [local=false]
)

AM_CONDITIONAL([LOCAL], [test x$local = xtrue])
if test x"$local" = x"true"; then
    AC_DEFINE([LOCAL],[],[Local interface])
    PKG_CHECK_MODULES([DEPS_LOCAL], [libmicrohttpd >= 0.9.3])
fi

# debug compilation support
AC_ARG_ENABLE(
    [debug],
    [AS_HELP_STRING([--enable-debug], [enable debug data generation (def=no)])],
    [debug=true],
    [debug=false]
)

if test x"$debug" = x"true"; then
    AC_DEFINE([DEBUG],[],[Debug Mode])
    AM_CXXFLAGS="$AM_CXXFLAGS -g -Wall -Werror -Wno-uninitialized -O0"
else
    AM_CXXFLAGS="$AM_CXXFLAGS -O3"
fi

AC_OUTPUT

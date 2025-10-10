import React from "react";
import '@/app/(home)/auth.css'

const Logo = async () => {

  return (
     <>
      <div className="auth-container">
        <div className="auth-illustration">
          <div className="auth-illustration-logo">
            {/* <!-- The falling lines that create the kinetic effect --> */}
            <div className="kinetic-line line-1"></div>
            <div className="kinetic-line line-2"></div>
            <div className="kinetic-line line-3"></div>
            <div className="kinetic-line line-4"></div>
            <div className="kinetic-line line-5"></div>
            <div className="kinetic-line line-6"></div>
            <div className="kinetic-line line-7"></div>
            <div className="kinetic-line line-8"></div>
            <div className="kinetic-line line-9"></div>
            <div className="logo-container">
              <svg className="logo-svg" viewBox="0 0 100 100">
                {/* <!-- Box Path --> */}
                <path className="logo-path" d="M 10 10 L 90 10 L 90 90 L 10 90 Z" />
                {/* <!-- Letter N Path --> */}
                <path className="logo-path" d="M 35 75 L 35 25 L 65 75 L 65 25" />
              </svg>
              <div className="logo-text-container">
                <div className="logo-text">NEXT</div>
                <div className="logo-subtext">YOUR BUSINESS PARTNER</div>
              </div>
            </div>
          </div>
        </div>
      </div>

   
   </>
  );
};

export default Logo;
